<?php

use Illuminate\Support\Facades\Route;

// ================== Controllers ==================

// Auth
use App\Http\Controllers\AuthController;

// Landing
use App\Http\Controllers\HomeController;

// Admin
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PayRunWizardController;
use App\Http\Controllers\Admin\EmployeeController as AdminEmployeeController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\AttendanceRequestController as AdminAttendanceRequestController;
use App\Http\Controllers\Admin\OvertimeRequestController as AdminOvertimeRequestController;

//* Leave Modul */
use App\Http\Controllers\Admin\LeaveRequestController as AdminLeaveRequestController;
use App\Http\Controllers\Admin\LeaveTypeController as AdminLeaveTypeController;
use App\Http\Controllers\Admin\LeavePolicyController as AdminLeavePolicyController;
use App\Http\Controllers\Admin\LeaveEntitlementController as AdminLeaveEntitlementController;
use App\Http\Controllers\Admin\LeaveLedgerController as AdminLeaveLedgerController;
use App\Http\Controllers\Admin\LeaveReportController as AdminLeaveReportController;
use App\Http\Controllers\Admin\LeaveEntitlementGenerateController as AdminLeaveEntitlementGenerateController;


use App\Http\Controllers\Admin\AttendanceSummaryController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\SettingOvertimesController;
use App\Http\Controllers\Admin\CompanyBankAccountController;
use App\Http\Controllers\Admin\EmployeeAllowanceController;
use App\Http\Controllers\Admin\EmployeeDeductionController;
use App\Http\Controllers\Admin\PayComponentController;
use App\Http\Controllers\Admin\PayComponentRateController;
use App\Http\Controllers\Admin\PayGroupController;
use App\Http\Controllers\Admin\PayGroupComponentController;
use App\Http\Controllers\Admin\PayRunAuditController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\WorkScheduleController;
use App\Http\Controllers\Admin\WorkScheduleExportController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\TransportRouteController;
use App\Http\Controllers\Admin\AttendanceLocationSettingController as AdminAttendanceLocationSettingController;
use App\Http\Controllers\Admin\ShiftChangeApprovalController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\Admin\EmployeeImportController;


// Employee (ESS)
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\NotificationController as EmployeeNotificationController;
use App\Http\Controllers\Employee\AttendanceController as EmployeeAttendanceController;
use App\Http\Controllers\Employee\AttendanceRequestController as EmployeeAttendanceRequestController;
use App\Http\Controllers\Employee\OvertimeRequestController as EmployeeOvertimeRequestController;
use App\Http\Controllers\Employee\LeaveRequestController as EmployeeLeaveRequestController;
use App\Http\Controllers\Employee\PayslipController as EmployeePayslipController;
use App\Http\Controllers\Employee\ShiftChangeController as EmployeeShiftChangeController;

// Superadmin
use App\Http\Controllers\Superadmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\Superadmin\UserController as SuperUserController;


// ==================================================
// Public / Guest
// ==================================================
// Fungsi: Halaman login dan aksi login untuk user yang belum terautentik.
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

    // (Opsional) Reset password / register bila dibutuhkan
    // Route::get('/forgot-password', ...)->name('password.request');
    // Route::post('/forgot-password', ...)->name('password.email');
    // Route::get('/register', ...)->name('register');
    // Route::post('/register', ...);
});

// ==================================================
// Auth umum
// ==================================================
// Fungsi: Logout user yang sudah login.
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')->name('logout');

// ==================================================
// Landing / Home
// ==================================================
// Fungsi: Halaman landing setelah login (dashboard pintar ringkas).
Route::get('/', HomeController::class)
    ->middleware(['web','auth:web'])->name('home');


    Route::get('/about-us', function () {
    return view('auth.about-us');
})->name('about-us');

// ==================================================
// ADMIN AREA
// ==================================================
// Catatan: Gunakan guard eksplisit ['web','auth:web'] untuk hindari guard mismatch.
// Permission detail sebagian dikelola per-route group, sebagian di constructor controller.
Route::prefix('admin')->as('admin.')->middleware(['web','auth:web'])->group(function () {

    // ---------------- Dashboard ----------------
    // Fungsi: Dashboard admin (KPI ringkas, widget ringkas).
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // ---------------- User Role Manage ----------------
    // Fungsi: Kelola role user (khusus admin sistem).
    Route::middleware('permission:user.role.manage')->group(function () {
        Route::get('user-roles',             [UserRoleController::class,'index'])->name('user-roles.index');
        Route::get('user-roles/{user}/edit', [UserRoleController::class,'edit'])->name('user-roles.edit');
        Route::put('user-roles/{user}',      [UserRoleController::class,'update'])->name('user-roles.update');
    });

    // ---------------- Payroll — Pay Runs ----------------
    // Fungsi: Lihat daftar pay run & review detail.
    // READ-ONLY
        Route::middleware('permission:payroll.run.view')->group(function () {
            Route::get('/payruns',                [PayRunWizardController::class, 'index'])->name('payruns.index');
            Route::get('/payruns/{payrun}/review',[PayRunWizardController::class, 'review'])->name('payruns.review');
            Route::get('/payruns/export',         [PayRunWizardController::class, 'export'])->name('payruns.export');
        });

        // CREATE/STORE
        Route::get('/payruns/create', [PayRunWizardController::class, 'create'])
            ->middleware(['permission:payroll.run.view','permission:payroll.run.create'])
            ->name('payruns.create');

        Route::post('/payruns', [PayRunWizardController::class, 'store']) // ← RESTful
            ->middleware('permission:payroll.run.create')
            ->name('payruns.store');

        // ACTIONS — pakai POST semua
        Route::post('/payruns/{payrun}/simulate', [PayRunWizardController::class, 'simulate'])
            ->middleware('permission:payroll.run.simulate')
            ->name('payruns.simulate');

        Route::post('/payruns/{payrun}/finalize', [PayRunWizardController::class, 'finalize'])
            ->middleware('permission:payroll.run.finalize')
            ->name('payruns.finalize');

        Route::post('/payruns/{payrun}/unfinalize', [PayRunWizardController::class, 'unfinalize'])
            ->middleware('permission:payroll.run.reopen')
            ->name('payruns.unfinalize');

        Route::post('/payruns/{payrun}/lock', [PayRunWizardController::class, 'lock'])
            ->middleware('permission:payroll.run.finalize')
            ->name('payruns.lock');

        Route::post('/payruns/{payrun}/reopen', [PayRunWizardController::class, 'reopen'])
            ->middleware('permission:payroll.run.reopen')
            ->name('payruns.reopen');


        // SHOW ITEMS
        Route::get('/payruns/{payrun}/items/{item}', [PayRunWizardController::class, 'showItem'])
            ->name('payruns.items.show')
            ->middleware('permission:payroll.run.view');


    // ---------------- Payroll — Audit ----------------
         Route::middleware('permission:payroll.rate.manage')->group(function () {
            Route::resource('payruns-audit', PayRunAuditController::class)
                ->shallow()
                ->only(['index']);

     });

    // ---------------- Payroll — Master ----------------
    // Fungsi: Master komponen/rate/group payroll (HR/Payroll Admin).
    Route::middleware('permission:payroll.rate.manage')->group(function () {
        Route::resource('pay-components',        PayComponentController::class)->except(['show']);
       
        Route::post('pay-components/{payComponent}/archive', [PayComponentController::class, 'archive'])
            ->name('pay-components.archive');
        Route::post('pay-components/{payComponent}/restore', [PayComponentController::class, 'restore'])
            ->name('pay-components.restore');

        Route::resource('pay-groups',           PayGroupController::class)->except(['show']);
        Route::resource('pay-groups.components', PayGroupComponentController::class)
                ->shallow()
                ->except(['show']);

        Route::resource('pay-components.rates', PayComponentRateController::class)
                ->shallow()
                ->only(['index','create','store','edit','update','destroy']);
    });

    // ---------------- HR — Attendance (View) ----------------
    // Fungsi: Lihat data absensi & summary (read-only).
            Route::middleware('permission:hr.attendance.view')->group(function () {
                Route::get('/attendance', [AdminAttendanceController::class, 'index'])
                    ->name('attendance.index');

                Route::get('/attendance/summary', [AttendanceSummaryController::class, 'index'])
                    ->name('attendance-summary.index');

                // Tambahan: Export (csv/xlsx/pdf)
                Route::get(
                    '/attendance/summary/export/{format}',
                    [AttendanceSummaryController::class, 'export']
                )->whereIn('format', ['csv','xlsx','pdf'])
                ->name('attendance-summary.export');
            });

            Route::get('/attendance/{employee}/detail', [AttendanceSummaryController::class, 'detail'])
        ->name('attendance.detail');


    // ---------------- HR — Attendance (Approval) ----------------
    // Fungsi: Approve/reject pengajuan terkait attendance/OT/leave.
    Route::middleware('permission:hr.attendance.approve')->group(function () {

        // ========== Attendance Requests ==========
        // Custom actions (status change) -> PATCH + implicit binding
        Route::prefix('attendance-requests')->name('attendance-requests.')->group(function () {
            Route::patch('{attendance_request}/approve', [AdminAttendanceRequestController::class, 'approve'])
                ->name('approve');
            Route::patch('{attendance_request}/reject',  [AdminAttendanceRequestController::class, 'reject'])
                ->name('reject');
        });

        Route::resource('attendance-requests', AdminAttendanceRequestController::class)
            ->only(['index','show','update'])
            ->names('attendance-requests');

        // ========== Overtime Requests ==========
        Route::prefix('overtime-requests')->name('overtime-requests.')->group(function () {
            Route::patch('{overtime_request}/approve', [AdminOvertimeRequestController::class, 'approve'])
                ->name('approve');
            Route::patch('{overtime_request}/reject',  [AdminOvertimeRequestController::class, 'reject'])
                ->name('reject');
            Route::get('export', [AdminOvertimeRequestController::class, 'exportExcel'])
                ->name('export');
        });

        // Kembalikan resource untuk UI web (index + lainnya sesuai kebutuhan)
            Route::resource('overtime-requests', AdminOvertimeRequestController::class)
                ->except(['show']); // atau atur sesuai modulmu

        // ========== Leave Requests ==========
            Route::prefix('leave-requests')->name('leave-requests.')->group(function () {
            Route::patch('{leave}/approve', [AdminLeaveRequestController::class, 'approve'])
                ->name('approve');
            Route::patch('{leave}/reject',  [AdminLeaveRequestController::class, 'reject'])
                ->name('reject');
            });
            Route::resource('leave-requests', AdminLeaveRequestController::class)
                ->only(['index','show','update'])
            ->names('leave-requests');

            // 4) Leave Types
            Route::resource('leave-types', AdminLeaveTypeController::class);

            // 5) Leave Policies
            Route::resource('leave-policies', AdminLeavePolicyController::class);

            // 6) Leave Entitlements
            Route::get('leave-entitlements', [AdminLeaveEntitlementController::class, 'index'])->name('leave-entitlements.index');
            Route::post('leave-entitlements/adjust', [AdminLeaveEntitlementController::class, 'adjust'])->name('leave-entitlements.adjust');
            // 6b) Generate Entitlements (NEW)Admin
            Route::prefix('leave-entitlements')->name('leave-entitlements.')->group(function () {
                // Halaman form generate
                Route::get('generate',  [AdminLeaveEntitlementGenerateController::class, 'index'])
                    ->name('generate.form');     // admin.leave-entitlements.generate.form

                // Proses generate
                Route::post('generate', [AdminLeaveEntitlementGenerateController::class, 'store'])
                    ->name('generate.store');    // admin.leave-entitlements.generate.store

                // AJAX search karyawan (multi-select)
                Route::get('employee-search', [AdminLeaveEntitlementGenerateController::class, 'employeeSearch'])
                    ->name('employee-search');   // admin.leave-entitlements.employee-search
            });

            // 7) Leave Ledger
            Route::get('leave-ledger', [AdminLeaveLedgerController::class, 'index'])->name('leave-ledger.index');

            // 8) Leave Reports
            Route::get('leave-reports', [AdminLeaveReportController::class, 'index'])->name('leave-reports.index');
        });


    // ---------------- Schedule — Work Schedules ----------------
    // Fungsi: Kelola jadwal kerja (generate/assign jadwal).
        Route::middleware('permission:work-schedule.manage')->group(function () {
        Route::get('/work-schedules', [WorkScheduleController::class, 'index'])
            ->name('work-schedules.index');

        Route::post('/work-schedules/generate', [WorkScheduleController::class, 'generate'])
            ->name('work-schedules.generate');

        Route::get('/work-schedules/events', [WorkScheduleController::class, 'events'])
            ->name('work-schedules.events');

        // GET: halaman form import
        Route::get('/work-schedules/import', [WorkScheduleController::class, 'showImport'])
            ->name('work-schedules.import.page');


        // ✅ Step 1: Upload & parse Excel
        Route::post('/work-schedules/import', [WorkScheduleController::class, 'import'])
            ->name('work-schedules.import');

        // ✅ Step 2: Tampilkan hasil parsing
        Route::get('/work-schedules/confirm', [WorkScheduleController::class, 'confirmPage'])
            ->name('work-schedules.confirm');

        // ✅ Step 3: Simpan hasil ke DB
        Route::post('/work-schedules/import/confirm', [WorkScheduleController::class, 'confirmStore'])
            ->name('work-schedules.import.confirmed');

        Route::get('/work-schedules/export', [WorkScheduleExportController::class, 'index'])
            ->name('workschedule.export');

        Route::post('/work-schedules/export/template', [WorkScheduleExportController::class, 'download'])
            ->name('workschedule.export.download');

                // 🔌 AJAX cascading dropdown
        Route::get('/ajax/sections',  [SectionController::class,  'byDepartment'])
            ->name('sections.byDepartment');   // route('admin.sections.byDepartment') di Blade
        Route::get('/ajax/positions', [PositionController::class, 'bySections'])
            ->name('positions.bySections');    // route('admin.positions.bySections')
        });



    // ---------------- Shifts ----------------
    // Fungsi: Kelola master shift.
    Route::resource('shifts', ShiftController::class)
        ->only(['index','store','update','destroy'])
        ->names('shifts')
        ->middleware('permission:shift.manage');

    // ---------------- HR — Employees & Organization ----------------
    // Fungsi: Master karyawan & organisasi (lihat/kelola).
    // NOTE: Permission per-action dikelola di constructor AdminEmployeeController:
    //   - index/show  : hr.employee.view_basic
    //   - create/edit : hr.employee.manage
    Route::resource('employees', AdminEmployeeController::class)->names('employees');

     // ---------------- HR — Employees Import  ----------------
    // routes/web.php (di dalam group admin + auth)
     Route::prefix('employee-import')
            ->name('employee.import.')
            ->group(function () {
                // Step 1: form upload
                Route::get('/', [EmployeeImportController::class, 'form'])->name('form');

                // Step 2: preview (POST proses file -> redirect GET; GET untuk pagination)
                Route::match(['get','post'], '/preview', [EmployeeImportController::class, 'preview'])->name('preview');

                // Step 3: commit ke DB
                Route::post('/store', [EmployeeImportController::class, 'store'])->name('store');

                // (Opsional) download template
                Route::get('/template', [EmployeeImportController::class, 'downloadTemplate'])->name('template');
            });


    Route::delete('employee/bulk-delete', [AdminEmployeeController::class, 'bulkDelete'])->name('employee.bulk-delete');

    // Fungsi: Master organisasi: department/position/group/section.
    Route::resource('departments', DepartmentController::class)->names('departments')
        ->middleware('permission:org.manage');
    Route::resource('positions', PositionController::class)->names('positions')
        ->middleware('permission:org.manage');
    Route::resource('groups', GroupController::class)->names('groups')
        ->middleware('permission:org.manage');
      
    Route::post('groups/{group}/employees', [GroupController::class, 'addEmployee'])
        ->name('groups.employees.add');

    Route::delete('groups/{group}/employees/{employee}', [GroupController::class, 'removeEmployee'])
        ->name('groups.employees.remove');
    Route::resource('sections', SectionController::class)->names('sections')
        ->middleware('permission:org.manage');

    // Fungsi: Pengaturan lokasi absensi.
    Route::resource('attendance-location-settings', AdminAttendanceLocationSettingController::class)
        ->names('attendance-location-settings')
        ->middleware('permission:attendance.location.manage');

    // Fungsi: Transport route (opsional).
    Route::resource('transportroutes', TransportRouteController::class)->names('transportroutes')
        ->middleware('permission:transport.setting.manage');

    // Fungsi: Approval pindah shift.
    Route::middleware('permission:shift-change.request.approve')->group(function () {
        Route::get('shift-change-requests',                 [ShiftChangeApprovalController::class, 'index'])->name('shift-change.index');
        Route::patch('shift-change-requests/{id}/approve',  [ShiftChangeApprovalController::class, 'approve'])->name('shift-change.approve');
        Route::patch('shift-change-requests/{id}/reject',   [ShiftChangeApprovalController::class, 'reject'])->name('shift-change.reject');
    });

    // ---------------- Admin Users / System Admin ----------------
    // Fungsi: Kelola user sistem & rekening perusahaan (khusus system-admin/super-admin).
    Route::middleware('role:system-admin|super-admin')->group(function () {
        Route::resource('users', AdminUserController::class)->names('users');
        Route::resource('company-bank-accounts', CompanyBankAccountController::class)->names('company-bank-accounts');
    });

    // ---------------- Notification ----------------
    // Fungsi: Notifikasi untuk admin/HR.
    Route::get('/notifications',                  [AdminNotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [AdminNotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::patch('/notifications/read-all',            [AdminNotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
});


// ==================================================
// EMPLOYEE SELF-SERVICE (ESS)
// ==================================================
// Fungsi: Portal karyawan untuk melihat notifikasi, absensi, pengajuan, payslip, dsb.
Route::prefix('employee')->middleware(['web','auth:web'])->group(function () {
    // Dashboard & Notifications
    
    Route::get('/',                [EmployeeDashboardController::class, 'index'])->name('employee.dashboard');
    Route::get('/notifications',   [EmployeeNotificationController::class, 'index'])->name('employee.notifications');
    Route::patch('/notifications/{notification}/read', [EmployeeNotificationController::class, 'markAsRead'])
            ->name('notifications.markAsRead');
    Route::patch('/notifications/read-all', [EmployeeNotificationController::class, 'markAllAsRead'])
            ->name('notifications.markAllAsRead');

    // Attendance & Requests (karyawan mengajukan)
    Route::get('attendance', [EmployeeAttendanceController::class, 'create'])->name('employee.attendance.create');
    Route::post('attendance', [EmployeeAttendanceController::class, 'store'])->name('employee.attendance.store');
    Route::get('attendance/history', [EmployeeAttendanceController::class, 'index'])->name('employee.attendance.index');
    Route::get('attendance/{attendance}', [EmployeeAttendanceController::class, 'show'])->name('employee.attendance.show');
    Route::post('attendance/update-late-reason', [EmployeeAttendanceController::class, 'updateLateReason'])->name('employee.attendance.updateLateReason');

    // Pengajuan Presens
    Route::get('attendance/requests/history', [EmployeeAttendanceRequestController::class, 'history'])->name('employee.attendance.requests.history');
    Route::get('attendance/requests/create', [EmployeeAttendanceRequestController::class,'create'])->name('employee.attendance.requests.create');
    Route::post('attendance/requests', [EmployeeAttendanceRequestController::class,'store'])->name('employee.attendance.requests.store');
    Route::get('attendance/requests/{id}', [EmployeeAttendanceRequestController::class,'show'])->name('employee.attendance.requests.show');

    // Overtime Requests
    Route::get('overtime-requests', [EmployeeOvertimeRequestController::class,'history'])->name('employee.overtime.requests.history');
    Route::get('overtime-requests/create', [EmployeeOvertimeRequestController::class,'create'])->name('employee.overtime.requests.create');
    Route::post('overtime-requests', [EmployeeOvertimeRequestController::class,'store'])->name('employee.overtime.requests.store');
    Route::get('overtime-requests/{id}', [EmployeeOvertimeRequestController::class,'show'])->name('employee.overtime.requests.show');

     // Leave Requests
    Route::get('leave', [EmployeeLeaveRequestController::class,'history'])->name('employee.leave.history');
    Route::get('leave/request', [EmployeeLeaveRequestController::class,'create'])->name('employee.leave.request');
    Route::post('leave/store', [EmployeeLeaveRequestController::class,'store'])->name('employee.leave.store');
    Route::get('leave/{id}', [EmployeeLeaveRequestController::class,'show'])->name('employee.leave.show');

    
     // Shift Change Request
    Route::get('/shift-change-requests/create', [EmployeeShiftChangeController::class, 'create'])->name('employee.shift-change-requests.create');
    Route::post('/shift-change-requests/store', [EmployeeShiftChangeController::class, 'store'])->name('employee.shift-change-requests.store');
    Route::get('/shift-change-requests/history', [EmployeeShiftChangeController::class, 'history'])->name('employee.shift-change-requests.history');

    // Payslip (self)
    Route::middleware('permission:payroll.payslip.view_self')->group(function () {
        Route::get('payslip',               [EmployeePayslipController::class, 'index'])->name('employee.payslip.index');
        Route::get('payslip/{payroll}',     [EmployeePayslipController::class, 'show'])->name('employee.payslip.show');
        Route::get('payslip/{payroll}/pdf', [EmployeePayslipController::class, 'downloadPdf'])->name('employee.payslip.pdf');
    });

    
});


// ==================================================
// SUPERADMIN (tanpa duplikasi controller)
// ==================================================
// Fungsi: Shortcut area superadmin; gunakan controller admin yang sama.
Route::prefix('superadmin')
    ->as('superadmin.')
    ->middleware(['web','auth:web','role:super-admin'])
    ->group(function () {

    Route::get('/', fn () => redirect()->route('superadmin.dashboard'));

    // GANTI baris ini:
    // Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // MENJADI:
    Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])
        ->name('dashboard');

    // Shortcut manajemen role via admin routes
    Route::get('user-roles', fn() => redirect()->route('admin.user-roles.index'))->name('user-roles.index');

    // (Opsional) Shortcut ke halaman employees admin
    // Route::get('employees', fn () => redirect()->route('admin.employees.index'))->name('employees.index');
});


// ==================================================
// Debug / Tools (sementara, non-produksi)
// ==================================================
// Fungsi: Debug identitas/guard/izin saat sesi aktif.
Route::middleware(['web','auth:web'])->get('/__whoami', function () {
    return response()->json([
        'guard'               => auth()->guard()->getName(),
        'user_id'             => auth()->id(),
        'roles'               => auth()->user()?->getRoleNames(),
        'is_superadmin'       => auth()->user()?->hasRole('super-admin'),
        'can_employee_basic'  => auth()->user()?->can('hr.employee.view_basic'),
    ]);
});
