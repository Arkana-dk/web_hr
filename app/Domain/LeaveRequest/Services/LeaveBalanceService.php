<?php

namespace App\Domain\LeaveRequest\Services;

use App\Domain\LeaveRequest\Contracts\LeaveBalanceServiceContract;
use App\Models\{Employee, LeaveEntitlement};
use Illuminate\Support\Carbon;

class LeaveBalanceService implements LeaveBalanceServiceContract
{
    public function getPeriodEntitlement(Employee $emp, int $leaveTypeId, Carbon $onDate): ?LeaveEntitlement
    {
        return LeaveEntitlement::where('employee_id', $emp->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('period_start', '<=', $onDate)
            ->where('period_end', '>=', $onDate)
            ->first();
    }

    public function computeBalance(LeaveEntitlement $e): float
    {
        return (float) $e->opening_balance + (float) $e->accrued + (float) $e->adjustments - (float) $e->used;
    }
}
