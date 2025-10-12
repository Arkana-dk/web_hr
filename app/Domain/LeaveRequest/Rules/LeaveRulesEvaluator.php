<?php

namespace App\Domain\LeaveRequest\Rules;

use Illuminate\Support\Carbon;

class LeaveRulesEvaluator
{
    public function validate(array $rules, Carbon $start, Carbon $end, array $context): void
    {
        $today = Carbon::today();

        // Minimal jarak pengajuan (H - x)
        $minNotice = $rules['min_notice_days'] ?? null;
        if ($minNotice !== null && $start->diffInDays($today, false) > -$minNotice) {
            throw new \RuntimeException("Pengajuan minimal {$minNotice} hari sebelum mulai.");
        }

        // Maks sejauh apa pengajuan (H + x)
        $maxLead = $rules['max_lead_days'] ?? null;
        if ($maxLead !== null && $today->diffInDays($start) > $maxLead) {
            throw new \RuntimeException("Tanggal mulai terlalu jauh (maks {$maxLead} hari dari hari ini).");
        }

        // Restriksi gender (opsional)
        if (!empty($rules['gender_restriction']) && $rules['gender_restriction'] !== 'any') {
            $empGender = $context['employee_gender'] ?? null;
            if ($empGender && strtolower($empGender) !== strtolower($rules['gender_restriction'])) {
                throw new \RuntimeException("Jenis cuti ini hanya untuk {$rules['gender_restriction']}.");
            }
        }

        // Maksimal lama per pengajuan
        $maxPerReq = $rules['max_days_per_request'] ?? null;
        if ($maxPerReq !== null && ($context['days'] ?? 0) > $maxPerReq) {
            throw new \RuntimeException("Maksimum durasi per pengajuan adalah {$maxPerReq} hari.");
        }

        // Batas per bulan / overlap cek → dilakukan di service (butuh query DB)
    }
}
