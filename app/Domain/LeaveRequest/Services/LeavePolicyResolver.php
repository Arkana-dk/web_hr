<?php

namespace App\Domain\LeaveRequest\Services;

use App\Domain\LeaveRequest\Contracts\LeavePolicyResolverContract;
use App\Models\{Employee, LeavePolicy};
use Illuminate\Support\Carbon;

class LeavePolicyResolver implements LeavePolicyResolverContract
{
    public function resolve(Employee $emp, int $leaveTypeId, Carbon $onDate): ?LeavePolicy
    {
        return LeavePolicy::where('leave_type_id', $leaveTypeId)
            ->where('effective_start', '<=', $onDate)
            ->where(function ($q) use ($onDate) {
                $q->whereNull('effective_end')->orWhere('effective_end', '>=', $onDate);
            })
            ->where(function ($q) use ($emp) {
                $q->where('pay_group_id', $emp->pay_group_id)
                  ->orWhereNull('pay_group_id'); // global default
            })
            ->orderByRaw('pay_group_id is null')
            ->orderByDesc('effective_start')
            ->first();
    }
}
