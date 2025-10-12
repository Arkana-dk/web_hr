<?php

namespace App\Domain\LeaveRequest\Contracts;

use App\Models\{Employee, LeaveRequest};
use Illuminate\Support\Carbon;

interface LeaveRequestServiceContract
{
    public function create(
        Employee $emp,
        int $leaveTypeId,
        Carbon $start,
        Carbon $end,
        ?string $reason = null,
        ?string $attachmentPath = null
    ): LeaveRequest;

    public function approve(LeaveRequest $lr, int $approverId): void;

    public function reject(LeaveRequest $lr): void;
}
