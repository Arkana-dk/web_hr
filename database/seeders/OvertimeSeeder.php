<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class OvertimeSeeder extends Seeder
{
    /**
     * Seed data lembur untuk SEMUA karyawan (Agustus 2025).
     * - Weekday saja (Senin–Jumat)
     * - Prob. lembur 20% per hari
     * - Durasi 1–3 jam (start 18:00)
     * - status = 'approved' agar langsung kebaca payroll
     * - field wajib (day_type, meal_option, transport_route) diisi nilai sederhana
     */
    public function run(): void
    {
        $periodStart = Carbon::parse('2025-08-01');
        $periodEnd   = Carbon::parse('2025-08-31');

        // Ambil semua employee
        $employeeIds = \App\Models\Employee::pluck('id');
        if ($employeeIds->isEmpty()) {
            $this->command->warn('OvertimeSeeder: tidak ada employee.');
            return;
        }

        // Bersihkan data overtime pada periode tsb (pakai kolom `date`)
        DB::table('overtime_requests')
            ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->delete();

        $period = CarbonPeriod::create($periodStart, $periodEnd);
        $rows = [];

        foreach ($period as $day) {
            if ($day->isWeekend()) {
                // skip Sabtu/Minggu — kalau mau isi juga boleh, set day_type = 'weekend'
                continue;
            }

            foreach ($employeeIds as $eid) {
                // Probabilitas lembur 20% per weekday
                if (mt_rand(1, 100) <= 20) {
                    $hours = mt_rand(1, 3); // 1–3 jam
                    $start = $day->copy()->setTime(18, 0, 0);
                    $end   = $start->copy()->addHours($hours);

                    $rows[] = [
                        'employee_id'    => $eid,
                        'date'           => $day->toDateString(),
                        'start_time'     => $start->format('H:i:s'),
                        'end_time'       => $end->format('H:i:s'),
                        'day_type'       => 'weekday',     // atau 'weekend' jika isi akhir pekan
                        'meal_option'    => 'none',        // isi nilai default yang kamu pakai di UI
                        'transport_route'=> 'self',        // isi nilai default
                        'reason'         => 'Dummy lembur seeder',
                        'status'         => 'approved',    // supaya langsung terbaca payroll
                        'approved_by'    => null,          // biarkan null jika tidak wajib
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('overtime_requests')->insert($chunk);
        }

        $this->command->info('OvertimeSeeder: data lembur Agustus 2025 berhasil dibuat ✅');
    }
}
