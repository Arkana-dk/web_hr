<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

use App\Models\AttendanceRequest;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Department;

use App\Domain\WorkSchedule\Contracts\WorkScheduleImporterContract;
use App\Domain\WorkSchedule\Services\WorkScheduleImporter;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            WorkScheduleImporterContract::class,
            WorkScheduleImporter::class
        );
            // ==== LeaveRequest bindings ====
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

    public function boot(): void
    {
        // Penting: jangan jalankan composer view saat CLI (migrate/seed/etc.)
        if (app()->runningInConsole()) {
            return;
        }

        View::composer('*', function ($view) {
            // Default values
            $pendingAttendanceRequests = 0;
            $pendingLeaveRequests      = 0;
            $pendingOvertimeRequests   = 0;
            $departments               = collect();

            // Attendance Requests
            if (Schema::hasTable('attendance_requests')) {
                try {
                    $pendingAttendanceRequests = AttendanceRequest::where('status', 'pending')->count();
                } catch (\Throwable $e) { /* noop */ }
            }

            // Leave Requests
            if (Schema::hasTable('leave_requests')) {
                try {
                    $pendingLeaveRequests = LeaveRequest::where('status', 'pending')->count();
                } catch (\Throwable $e) { /* noop */ }
            }

            // Overtime Requests
            if (Schema::hasTable('overtime_requests')) {
                try {
                    $pendingOvertimeRequests = OvertimeRequest::where('status', 'pending')->count();
                } catch (\Throwable $e) { /* noop */ }
            }

            // Departments + employees
            if (Schema::hasTable('departments') && Schema::hasTable('employees')) {
                try {
                    $departments = Department::withCount('employees')->get();
                } catch (\Throwable $e) { /* noop */ }
            }

            $view->with('pendingAttendanceRequests', $pendingAttendanceRequests)
                 ->with('pendingLeaveRequests', $pendingLeaveRequests)
                 ->with('pendingOvertimeRequests', $pendingOvertimeRequests)
                 ->with('departments', $departments);
        });
    }
}
