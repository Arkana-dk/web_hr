<?php

namespace App\Domain\LeaveRequest;

use Illuminate\Support\ServiceProvider;

class LeaveRequestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Domain\LeaveRequest\Contracts\LeavePolicyResolverContract::class,
            \App\Domain\LeaveRequest\Services\LeavePolicyResolver::class
        );
        $this->app->bind(
            \App\Domain\LeaveRequest\Contracts\WorkingDaysCalculatorContract::class,
            \App\Domain\LeaveRequest\Services\WorkingDaysService::class
        );
        $this->app->bind(
            \App\Domain\LeaveRequest\Contracts\LeaveBalanceServiceContract::class,
            \App\Domain\LeaveRequest\Services\LeaveBalanceService::class
        );
        $this->app->bind(
            \App\Domain\LeaveRequest\Contracts\LeaveRequestServiceContract::class,
            \App\Domain\LeaveRequest\Services\LeaveRequestService::class
        );
    }

    public function boot(): void {}
}
