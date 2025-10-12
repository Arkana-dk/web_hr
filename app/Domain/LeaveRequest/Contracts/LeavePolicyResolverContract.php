<?php

namespace App\Domain\LeaveRequest\Contracts;

use App\Models\{Employee, LeavePolicy};
use Illuminate\Support\Carbon;

interface LeavePolicyResolverContract
{
    public function resolve(Employee $emp, int $leaveTypeId, Carbon $onDate): ?LeavePolicy;
}
