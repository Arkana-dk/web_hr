<?php

namespace App\Domain\LeaveRequest;

use App\Models\{Employee, LeaveEntitlement};
use Illuminate\Support\Carbon;
use App\Domain\LeaveRequest\Contracts\LeavePolicyResolverContract;

class EnsureEntitlement
{
    public function __construct(private LeavePolicyResolverContract $resolver) {}

    public function ensure(Employee $emp, int $leaveTypeId, Carbon $asOf): LeaveEntitlement
    {
        $start = (clone $asOf)->startOfYear();
        $end   = (clone $asOf)->endOfYear();

        $existing = LeaveEntitlement::where('employee_id', $emp->id)
            ->where('leave_type_id', $leaveTypeId)
            ->whereDate('period_start', '<=', $end)
            ->whereDate('period_end',   '>=', $start)
            ->first();

        if ($existing) return $existing;

        // Ambil policy lalu hitung quota (prorata bila perlu)
        $policy  = $this->resolver->resolve($emp, $leaveTypeId, $start) ?? ['rules'=>[]];
        $annual  = (float)($policy['rules']['annual_quota'] ?? 12);
        $prorate = (bool) ($policy['rules']['is_prorated'] ?? false);

        $join = $emp->tmt ? Carbon::parse($emp->tmt) : $start;
        $effectiveStart = $join->gt($start) ? $join : $start;
        $months = max(1, 12 - ($effectiveStart->month - 1));
        $accrued = $prorate ? round($annual * ($months/12), 2) : $annual;

        return LeaveEntitlement::create([
            'employee_id'     => $emp->id,
            'leave_type_id'   => $leaveTypeId,
            'period_start'    => $start,
            'period_end'      => $end,
            'opening_balance' => 0,
            'accrued'         => $accrued,
            'adjustments'     => 0,
            'used'            => 0,
            'note'            => 'auto-provision',
        ]);
    }
}
