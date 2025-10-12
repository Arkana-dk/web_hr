<?php

namespace App\Domain\LeaveRequest\Services;

use App\Domain\LeaveRequest\Contracts\WorkingDaysCalculatorContract;
use App\Models\{Employee, WorkSchedule};
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

class WorkingDaysService implements WorkingDaysCalculatorContract
{
    /**
     * Hitung jumlah hari kerja dari start..end.
     * - excludeWeekends: jika true, Sabtu(6)/Minggu(7) tidak dihitung.
     * - Mengacu ke WorkSchedule per-employee jika ada record untuk tanggal tsb.
     *   Heuristik “OFF”:
     *     - kolom is_off == 1, atau
     *     - kolom off == 1, atau
     *     - shift_id == null, atau
     *     - work_minutes == 0, atau
     *     - start_time & end_time sama2 null
     *   Jika tidak ada WorkSchedule, fallback: anggap hari kerja kecuali weekend (bila excludeWeekends = true).
     */
     public function count(Employee $emp, Carbon $start, Carbon $end, bool $excludeWeekends = true): float
    {
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $days = 0.0;
        foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $d) {
            $dow = $d->dayOfWeekIso; // 1=Mon ... 7=Sun
            if ($excludeWeekends && ($dow === 6 || $dow === 7)) {
                continue;
            }
            $days += 1.0;
        }

        return $days;
    }
}
