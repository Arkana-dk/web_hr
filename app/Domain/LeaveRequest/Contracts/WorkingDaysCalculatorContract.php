<?php

namespace App\Domain\LeaveRequest\Contracts;

use App\Models\Employee;
use Illuminate\Support\Carbon;

interface WorkingDaysCalculatorContract
{
    public function count(Employee $emp, Carbon $start, Carbon $end, bool $excludeWeekends = true): float;
}
