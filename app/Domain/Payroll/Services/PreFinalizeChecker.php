<?php

namespace App\Domain\Payroll\Services;

use App\Models\PayRun;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\OvertimeRequest;
use Illuminate\Support\Carbon;

class PreFinalizeChecker {
    public function check(PayRun $run): Collection {
        $issues = collect();

        // === Range untuk kolom DATETIME (overtime_requests.date kalau bertipe datetime) ===
        $startDT = $run->start_date instanceof \Carbon\Carbon
            ? $run->start_date->copy()->startOfDay()
            : Carbon::parse($run->start_date)->startOfDay();

        $endDT = $run->end_date instanceof \Carbon\Carbon
            ? $run->end_date->copy()->endOfDay()
            : Carbon::parse($run->end_date)->endOfDay();

        // === Range untuk kolom DATE (pay_component_rates.effective_*) ===
        $startDate = $run->start_date instanceof \Carbon\Carbon
            ? $run->start_date->toDateString()
            : (string) $run->start_date;

        $endDate = $run->end_date instanceof \Carbon\Carbon
            ? $run->end_date->toDateString()
            : (string) $run->end_date;

        // ✅ 1) Cek apakah masih ada lembur pending untuk pay group ini
        $pendingOT = OvertimeRequest::whereHas('employee', fn($q) =>
                $q->where('pay_group_id', $run->pay_group_id)
            )
            ->whereBetween('date', [$startDT, $endDT])   // aman untuk DATETIME
            ->where('status', 'pending')
            ->exists();

        if ($pendingOT) {
            $issues->push('Overtime masih pending');
        }

        // komponen yang dihitung rule & tidak butuh baris rate
        $rateOptional = ['PPH21_EE']; // tambah lainnya jika perlu

        $missingRates = DB::table('pay_group_components as pgc')
            ->join('pay_components as pc', 'pc.id', '=', 'pgc.pay_component_id')
            ->leftJoin('pay_component_rates as r', function ($j) use ($startDate, $endDate) {
                $j->on('r.pay_component_id', '=', 'pgc.pay_component_id')
                ->where('r.effective_start', '<=', $endDate)
                ->where(function ($q) use ($startDate) {
                    $q->whereNull('r.effective_end')
                        ->orWhere('r.effective_end', '>=', $startDate);
                })
                ->where(function ($q) {
                    $q->whereColumn('r.pay_group_id', 'pgc.pay_group_id')
                        ->orWhereNull('r.pay_group_id');
                });
            })
            ->where('pgc.pay_group_id', $run->pay_group_id)
            ->where('pgc.active', true)
            ->whereNull('r.id')
            ->whereNotIn('pc.code', $rateOptional)   // ⬅️ abaikan komponen tertentu
            ->distinct()
            ->pluck('pc.code')
            ->all();


        if (!empty($missingRates)) {
            $issues->push('Rate hilang: ' . implode(', ', $missingRates));
        }

        return $issues;
    }
}
