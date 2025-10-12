<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Facades\Excel;

class CobaAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // === KONFIG ===
        $excelPath   = database_path('seeders/data/jadwal-sept-2025.xlsx'); // ganti sesuai lokasi file kamu
        $sheetIndex  = 1; // sheet ke-2 = "Jadwal"
        $periodStart = Carbon::parse('2025-09-01');
        $periodEnd   = Carbon::parse('2025-09-30');
        $pAbsent     = 0.04;   // 4% alpa
        $pLate       = 0.15;   // 15% telat
        $officeLat   = -6.21462;
        $officeLng   = 106.84513;

        $offKeywords = ['LIBUR', 'LIBUR NASIONAL', 'CUTI', 'CUTI BERSAMA', 'IZIN', 'SAKIT'];

        // Bersihkan periode
        DB::table('work_schedules')
            ->whereBetween('work_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->delete();

        DB::table('attendances')
            ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->delete();

        // === BACA EXCEL ===
        $sheets = Excel::toCollection(null, $excelPath);
        if ($sheets->isEmpty() || !isset($sheets[$sheetIndex])) {
            $this->command->error("Sheet index {$sheetIndex} tidak ditemukan di file Excel.");
            return;
        }
        $rows = $sheets[$sheetIndex]->toArray();

        // Cari baris header: kolom A = USER_ID
        $headerRowIdx = null;
        foreach ($rows as $i => $r) {
            if (!empty($r[0]) && trim((string)$r[0]) === 'USER_ID') {
                $headerRowIdx = $i;
                break;
            }
        }
        if ($headerRowIdx === null) {
            $this->command->error('Tidak menemukan baris header (USER_ID, EMPLOYEE_NUMBER, NAMA).');
            return;
        }

        // Baris tanggal = baris setelah header
        $dateRow = $rows[$headerRowIdx + 1] ?? [];
        // Ambil semua tanggal mulai kolom index 3 (kolom D)
        $dates = [];
        for ($c = 3; $c < count($dateRow); $c++) {
            if (!empty($dateRow[$c])) {
                $d = Carbon::parse($dateRow[$c])->toDateString();
                if ($d >= $periodStart->toDateString() && $d <= $periodEnd->toDateString()) {
                    $dates[$c] = $d;
                }
            }
        }

        // === Tulis work_schedules dari template ===
        $wsRows = [];
        for ($r = $headerRowIdx + 2; $r < count($rows); $r++) {
            $row = $rows[$r] ?? [];
            if (empty(array_filter($row))) continue; // lewati baris kosong

            $userId = isset($row[0]) ? (int)$row[0] : null;
            $empNum = isset($row[1]) ? trim((string)$row[1]) : null;
            $name   = isset($row[2]) ? trim((string)$row[2]) : null;

            // Temukan employee
            $emp = null;
            if ($userId) {
                $emp = \App\Models\Employee::find($userId);
            }
            if (!$emp && $empNum) {
                $emp = \App\Models\Employee::where('employee_number', $empNum)->first();
            }
            if (!$emp && $name) {
                $emp = \App\Models\Employee::where('name', $name)->first();
            }
            if (!$emp) {
                $this->command->warn("Employee tidak ditemukan (row ".($r+1)."): {$userId} / {$empNum} / {$name}");
                continue;
            }

            foreach ($dates as $c => $date) {
                $cell = isset($row[$c]) ? trim((string)$row[$c]) : '';
                if ($cell === '' || $cell === '-' || strtoupper($cell) === 'NULL') continue;

                // Format umum: [7#1] NS → ambil shift_id = 7
                $shiftId = null; $shiftName = null;
                if (preg_match('/\[(\d+)#\d+\]\s*(.+)/', $cell, $m)) {
                    $shiftId   = (int)$m[1];
                    $shiftName = trim($m[2]);
                } else {
                    // fallback: cari by nama
                    $shiftName = $cell;
                    $s = \App\Models\Shift::where('name', 'like', $shiftName)->first();
                    $shiftId = $s?->id;
                }

                if (!$shiftId) {
                    $this->command->warn("Shift tidak dikenali untuk {$emp->name} {$date} → '{$cell}'");
                    continue;
                }

                $wsRows[] = [
                    'employee_id' => $emp->id,
                    'shift_id'    => $shiftId,
                    'work_date'   => $date,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];

                if (count($wsRows) >= 1000) {
                    DB::table('work_schedules')->insert($wsRows);
                    $wsRows = [];
                }
            }
        }
        if ($wsRows) DB::table('work_schedules')->insert($wsRows);

        $this->command->info('Work schedules dari Excel berhasil dibuat.');

        // === Generate ATTENDANCE dari work_schedules + shifts ===
        $schedules = \App\Models\WorkSchedule::with('shift:id,name,start_time,end_time')
            ->whereBetween('work_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->orderBy('work_date')
            ->get();

        $attRows = [];
        $total = 0;

        foreach ($schedules as $ws) {
            $date  = Carbon::parse($ws->work_date)->toDateString();
            $shift = $ws->shift;

            $name = strtoupper((string)($shift->name ?? ''));
            $isOff = false;
            foreach ($offKeywords as $kw) {
                if (str_contains($name, $kw)) { $isOff = true; break; }
            }

            if ($isOff || !$shift) {
                $attRows[] = [
                    'employee_id'          => $ws->employee_id,
                    'date'                 => $date,
                    'check_in_time'        => null,
                    'check_out_time'       => null,
                    'check_in_location'    => null,
                    'check_out_location'   => null,
                    'check_in_photo_path'  => null,
                    'check_out_photo_path' => null,
                    'check_in_latitude'    => null,
                    'check_in_longitude'   => null,
                    'check_out_latitude'   => null,
                    'check_out_longitude'  => null,
                    'status'               => 'excused',
                    'notes'                => trim($shift->name ?? 'Excused'),
                    'checkout_reason'      => null,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ];
                continue;
            }

            $start = Carbon::parse($date.' '.$shift->start_time);
            $end   = Carbon::parse($date.' '.$shift->end_time);
            if ($end->lessThanOrEqualTo($start)) $end->addDay(); // shift malam

            $r = mt_rand() / mt_getrandmax();
            $status = $r < $pAbsent ? 'absent' : ($r < $pAbsent + $pLate ? 'late' : 'present');

            $checkIn = null; $checkOut = null;
            if ($status !== 'absent') {
                $inOffset  = $status === 'late' ? rand(6, 25) : rand(-3, 4);
                $outOffset = rand(-12, 20);
                $checkIn   = $start->copy()->addMinutes($inOffset);
                $checkOut  = $end->copy()->addMinutes($outOffset);
            }

            $cinLat  = $checkIn  ? $officeLat + (rand(-50, 50) / 1_000_000) : null;
            $cinLng  = $checkIn  ? $officeLng + (rand(-50, 50) / 1_000_000) : null;
            $coutLat = $checkOut ? $officeLat + (rand(-50, 50) / 1_000_000) : null;
            $coutLng = $checkOut ? $officeLng + (rand(-50, 50) / 1_000_000) : null;

            $attRows[] = [
                'employee_id'           => $ws->employee_id,
                'date'                  => $date,
                'check_in_time'         => $checkIn?->format('H:i:s'),
                'check_out_time'        => $checkOut?->format('H:i:s'),
                'check_in_location'     => $checkIn ? 'Office Gate A' : null,
                'check_out_location'    => $checkOut ? 'Office Gate A' : null,
                'check_in_photo_path'   => $checkIn ? 'photos/checkin/'.Str::uuid().'.jpg' : null,
                'check_out_photo_path'  => $checkOut ? 'photos/checkout/'.Str::uuid().'.jpg' : null,
                'check_in_latitude'     => $cinLat,
                'check_in_longitude'    => $cinLng,
                'check_out_latitude'    => $coutLat,
                'check_out_longitude'   => $coutLng,
                'status'                => $status,
                'notes'                 => $shift->name,
                'checkout_reason'       => null,
                'created_at'            => now(),
                'updated_at'            => now(),
            ];

            if (count($attRows) >= 1000) {
                DB::table('attendances')->insert($attRows);
                $total += count($attRows);
                $attRows = [];
            }
        }

        if ($attRows) {
            DB::table('attendances')->insert($attRows);
            $total += count($attRows);
        }

        $this->command->info("CobaAttendanceSeeder (Sept 2025, dari Excel): {$total} baris dibuat ✅");
    }
}
