<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceSeeder extends Seeder
{
    /**
     * Seed presensi weekday (Senin–Jumat) untuk semua karyawan di beberapa Pay Group
     * pada periode Agustus 2025.
     */
    public function run(): void
    {
        // >>> Tambah pay group di sini
        $payGroupNames = [
            'Karyawan Staff Kantor',
            'Karyawan Produksi',
        ];

        $periodStart  = Carbon::parse('2025-09-01');
        $periodEnd    = Carbon::parse('2025-09-30z');

        // Jam kerja standar
        $shiftIn  = '08:00:00';
        $shiftOut = '17:00:00';

        // Probabilitas status
        $pAbsent  = 0.04;  // 4% alpa
        $pExcused = 0.04;  // 4% izin/cuti/training
        $pLate    = 0.15;  // 15% telat

        // Koordinat kantor (contoh)
        $officeLat = -6.21462;
        $officeLng = 106.84513;

        // Bersihkan data periode tsb sekali (aman untuk dev/testing)
        DB::table('attendances')
            ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->delete();

        $period = CarbonPeriod::create($periodStart, $periodEnd);
        $totalRows = 0;
        $rows = [];

        foreach ($payGroupNames as $payGroupName) {
            $group = \App\Models\PayGroup::where('name', $payGroupName)->first();
            if (!$group) {
                $this->command->warn("Pay Group '{$payGroupName}' tidak ditemukan. Lewati.");
                continue;
            }

            // Ambil daftar employee yang tergabung di pay group (kolom langsung / pivot)
            if (\Schema::hasColumn('employees', 'pay_group_id')) {
                $employeeIds = \App\Models\Employee::where('pay_group_id', $group->id)->pluck('id');
            } elseif (\Schema::hasTable('employee_pay_group')) {
                $employeeIds = DB::table('employee_pay_group')
                    ->where('pay_group_id', $group->id)
                    ->pluck('employee_id');
            } else {
                $employeeIds = collect();
            }

            if ($employeeIds->isEmpty()) {
                $this->command->warn("Tidak ada employee pada pay group '{$payGroupName}'. Lewati.");
                continue;
            }

            foreach ($period as $day) {
                if (!$day->isWeekday()) continue; // hanya Senin–Jumat

                foreach ($employeeIds as $eid) {
                    // Tentukan status
                    $r = mt_rand() / mt_getrandmax();
                    $status = 'present';
                    if ($r < $pAbsent) {
                        $status = 'absent';
                    } elseif ($r < $pAbsent + $pExcused) {
                        $status = 'excused';
                    } elseif ($r < $pAbsent + $pExcused + $pLate) {
                        $status = 'late';
                    }

                    // Jam masuk/keluar
                    $in  = null;
                    $out = null;
                    if (!in_array($status, ['absent','excused'], true)) {
                        $inMinuteOffset  = $status === 'late' ? rand(6, 25) : rand(-3, 4);
                        $outMinuteOffset = rand(-12, 20);

                        $in  = Carbon::parse($day->toDateString().' '.$shiftIn)->addMinutes($inMinuteOffset)->format('H:i:s');
                        $out = Carbon::parse($day->toDateString().' '.$shiftOut)->addMinutes($outMinuteOffset)->format('H:i:s');
                    }

                    // GPS dengan jitter kecil
                    $cinLat  = $in  ? $officeLat + (rand(-50, 50) / 1_000_000) : null;
                    $cinLng  = $in  ? $officeLng + (rand(-50, 50) / 1_000_000) : null;
                    $coutLat = $out ? $officeLat + (rand(-50, 50) / 1_000_000) : null;
                    $coutLng = $out ? $officeLng + (rand(-50, 50) / 1_000_000) : null;

                    $rows[] = [
                        'employee_id'           => $eid,
                        'date'                  => $day->toDateString(),
                        'check_in_time'         => $in,
                        'check_out_time'        => $out,
                        'check_in_location'     => $in  ? 'Office Gate A' : null,
                        'check_out_location'    => $out ? 'Office Gate A' : null,
                        'check_in_photo_path'   => $in  ? 'photos/checkin/'.Str::uuid().'.jpg' : null,
                        'check_out_photo_path'  => $out ? 'photos/checkout/'.Str::uuid().'.jpg' : null,
                        'check_in_latitude'     => $cinLat,
                        'check_in_longitude'    => $cinLng,
                        'check_out_latitude'    => $coutLat,
                        'check_out_longitude'   => $coutLng,
                        'status'                => $status, // present|late|absent|excused
                        'notes'                 => $status === 'excused' ? 'Izin/Training (dummy)' : null,
                        'checkout_reason'       => $out ? null : ($status === 'excused' ? 'Pulang lebih awal (izin)' : null),
                        'created_at'            => now(),
                        'updated_at'            => now(),
                    ];

                    if (count($rows) >= 1000) {
                        DB::table('attendances')->insert($rows);
                        $totalRows += count($rows);
                        $rows = [];
                    }
                }
            }
        }

        if (!empty($rows)) {
            DB::table('attendances')->insert($rows);
            $totalRows += count($rows);
        }

        $this->command->info("AttendanceSeeder: {$totalRows} baris presensi Agustus 2025 dibuat untuk group: ".implode(', ', $payGroupNames)." ✅");
    }
}
