<?php

namespace App\Domain\Payroll\Rules;

use App\Domain\Payroll\DTOs\CalcResult;
use App\Domain\Payroll\Rules\Contracts\PayrollRule;
use App\Models\Employee;

class DailyAllowanceRule implements PayrollRule
{

    public function apply($ctx, $employee, CalcResult $result): void
    {
        // jaga-jaga
        if (! $employee instanceof \App\Models\Employee) {
            return;
        }

        $code  = config('payroll.daily_allowance_component_code', 'DAILY_ALLOW'); // kode komponen
        $basis = config('payroll.daily_allowance_basis', 'present');              // 'present' | 'paid'

        // cek komponen ada & aktif di group
        $compId = $ctx['components_by_code'][$code] ?? null;
        if (!$compId) return;

        $activeInGroup = collect($ctx['group_components'])->first(function ($gc) use ($compId) {
            $gcCompId = $gc->pay_component_id ?? $gc->component_id ?? optional($gc->component)->id;
            return (int)$gcCompId === (int)$compId && (bool)($gc->active ?? true);
        });
        if (!$activeInGroup) return;

        // ambil rate yg berlaku (prioritas per-group → default)
        $groupId    = $ctx['pay_group']->id ?? null;
        $rateMap    = $ctx['rates'][$compId] ?? [];
        $candidates = $rateMap[$groupId] ?? ($rateMap['default'] ?? []);
        if (empty($candidates)) return;

        $rateRow = collect($candidates)->sortByDesc('effective_start')->first();
        $rate    = (float)($rateRow->rate ?? 0);
        if ($rate <= 0) return;

        // kuantitas: hari hadir / hari dibayar
        $eid   = $employee->id;
        $stats = $ctx['attendanceStats'][$eid] ?? null;
        if (!$stats) return;

        $qty = (int) ($basis === 'paid' ? ($stats['paid_days'] ?? 0) : ($stats['present_days'] ?? 0));
        if ($qty <= 0) return;

        $amount = round($qty * $rate, 2);

        $result->addLine([
            'componentCode' => $code,
            'componentType' => 'earning',
            'name'          => 'Daily Allowance',
            'quantity'      => $qty,
            'rate'          => $rate,
            'amount'        => $amount,
            'source'        => 'rule:daily_allowance',
        ]);
    }
}
