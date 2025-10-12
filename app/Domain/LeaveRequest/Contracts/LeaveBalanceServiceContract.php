<?php

namespace App\Domain\LeaveRequest\Contracts;

use App\Models\{Employee, LeaveEntitlement};
use Illuminate\Support\Carbon;

interface LeaveBalanceServiceContract
{
    public function getPeriodEntitlement(Employee $emp, int $leaveTypeId, Carbon $onDate): ?LeaveEntitlement;
    public function computeBalance(LeaveEntitlement $e): float;
}
