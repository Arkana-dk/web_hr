<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\{Payroll, Attendance, PayRun};
use App\Policies\{PayslipPolicy, AttendancePolicy, PayRunPolicy};

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Payroll::class    => PayslipPolicy::class,
        Attendance::class => AttendancePolicy::class,
        PayRun::class     => PayRunPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // super-admin bypass (opsional)
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });
    }
}
