<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Employee, Attendance, Department, LeaveRequest, PayRun, PayGroup, PayComponent};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $u     = auth()->user();
        $today = Carbon::today(config('app.timezone')); // Asia/Jakarta

        // --- flags akses ---
        $showHR = collect([
            'hr.employee.view_basic','hr.employee.view_sensitive','hr.attendance.view',
            'attendance-summary.view','attendance.location.manage',
            'work-schedule.manage','shift.manage','shift-change.request.approve','org.manage',
            'attendance-request.approve','overtime-request.approve','leave-request.approve',
        ])->contains(fn($p) => $u->can($p));

        $showPayroll = collect([
            'payroll.run.view','payroll.group.manage','payroll.group-component.manage',
            'payroll.component.manage','payroll.rate.manage','payroll.payslip.view_all',
        ])->contains(fn($p) => $u->can($p));

        // --- HR metrics (on-demand) ---
        $hr = [
            'totalEmployees'    => 0,
            'newHiresThisMonth' => 0,
            'employeesThisMonth'=> 0,
            'employeeTarget'    => 10,
            'employeePercent'   => 0,
            'presentToday'      => 0,
            'presentPercent'    => 0,
            'absentToday'       => 0,
            'absentPercent'     => 0,
            'totalDepartments'  => 0,
            'deptLabels'        => [],
            'deptCounts'        => [],
            'leaveLabels'       => [],
            'leaveCounts'       => [],
            'statusLabels'      => ['approved','rejected','pending'],
            'statusCounts'      => [0,0,0],
        ];

        if ($showHR) {
            $hr['totalEmployees']     = Employee::count();
            $hr['newHiresThisMonth']  = Employee::whereYear('created_at', $today->year)
                                                ->whereMonth('created_at', $today->month)->count();
            $hr['employeesThisMonth'] = $hr['newHiresThisMonth'];
            $hr['employeePercent']    = $hr['employeeTarget'] ? min(100, round($hr['employeesThisMonth'] / $hr['employeeTarget'] * 100, 1)) : 0;

            $hr['presentToday']   = Attendance::whereDate('date', $today)->where('status', 'present')->count();
            $hr['absentToday']    = Attendance::whereDate('date', $today)->where('status', 'absent')->count();
            $hr['presentPercent'] = $hr['totalEmployees'] ? round($hr['presentToday'] / $hr['totalEmployees'] * 100, 1) : 0;
            $hr['absentPercent']  = $hr['totalEmployees'] ? round($hr['absentToday']  / $hr['totalEmployees'] * 100, 1) : 0;

            $hr['totalDepartments'] = Department::count();

            $hr['deptLabels'] = Department::orderBy('name')->pluck('name');
            $hr['deptCounts'] = Department::withCount('employees')->orderBy('name')->pluck('employees_count');

           

            $statusRaw         = LeaveRequest::select('status', DB::raw('COUNT(*) total'))
                                    ->whereYear ('created_at', $today->year)
                                    ->whereMonth('created_at', $today->month)
                                    ->groupBy('status')->pluck('total','status');
            $hr['statusCounts'] = collect($hr['statusLabels'])->map(fn($s)=> $statusRaw[$s] ?? 0)->values();
        }

        // --- Payroll metrics (on-demand) ---
        $payroll = [
            'runThisMonth'   => ['draft'=>0,'simulated'=>0,'finalized'=>0],
            'totalGroups'    => 0,
            'totalComponents'=> 0,
        ];

        if ($showPayroll) {
            $runs = PayRun::select('status', DB::raw('COUNT(*) total'))
                        ->whereYear('start_date', $today->year)
                        ->whereMonth('start_date', $today->month)
                        ->groupBy('status')->pluck('total','status');
            $payroll['runThisMonth']['draft']     = $runs['draft']     ?? 0;
            $payroll['runThisMonth']['simulated'] = $runs['simulated'] ?? 0;
            $payroll['runThisMonth']['finalized'] = $runs['finalized'] ?? 0;

            $payroll['totalGroups']     = PayGroup::count();
            $payroll['totalComponents'] = PayComponent::count();
        }

        // --- Data untuk view ---
        $data = array_merge(
            ['showHR' => $showHR, 'showPayroll' => $showPayroll],
            $hr,
            [
                'payroll_runThisMonth'   => $payroll['runThisMonth'],
                'payroll_totalGroups'    => $payroll['totalGroups'],
                'payroll_totalComponents'=> $payroll['totalComponents'],
            ],
        );

        // --- Pilih view berdasar prefix route ---
        $view = request()->routeIs('superadmin.*') && view()->exists('superadmin.pages.dashboard')
            ? 'superadmin.pages.dashboard'
            : 'admin.pages.dashboard';

        return view($view, $data);
    }
}
