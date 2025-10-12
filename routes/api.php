<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Auth
use App\Http\Controllers\Api\AuthController;

// ADMIN API Controllers
use App\Http\Controllers\Api\Admin\RecruitmentController           as ApiRecruitmentController;
use App\Http\Controllers\Api\Admin\EmployeeController              as ApiEmployeeController;
use App\Http\Controllers\Api\Admin\AttendanceController            as ApiAdminAttendanceController;
use App\Http\Controllers\Api\Admin\AttendanceRequestController     as ApiAdminAttendanceRequestController;
use App\Http\Controllers\Api\Admin\OvertimeRequestController       as ApiAdminOvertimeRequestController;
use App\Http\Controllers\Api\Admin\LeaveRequestController          as ApiAdminLeaveRequestController;
use App\Http\Controllers\Api\Admin\AttendanceSummaryController     as ApiAttendanceSummaryController;
use App\Http\Controllers\Api\Admin\TunjanganController             as ApiTunjanganController;
use App\Http\Controllers\Api\Admin\PotonganController              as ApiPotonganController;
use App\Http\Controllers\Api\Admin\CompanyBankAccountController    as ApiCompanyBankAccountController;
use App\Http\Controllers\Api\Admin\EmployeeAllowanceController     as ApiEmployeeAllowanceController;
use App\Http\Controllers\Api\Admin\EmployeeDeductionController     as ApiEmployeeDeductionController;
use App\Http\Controllers\Api\Admin\DepartmentController            as ApiDepartmentController;
use App\Http\Controllers\Api\Admin\PositionController              as ApiPositionController;
use App\Http\Controllers\Api\Admin\SettingOvertimesController      as ApiSettingOvertimesController;
use App\Http\Controllers\Api\Admin\PayrollController               as ApiPayrollController;
use App\Http\Controllers\Api\Admin\ShiftController                 as ApiShiftController;
use App\Http\Controllers\Api\Admin\ShiftGroupController            as ApiShiftGroupController;
use App\Http\Controllers\Api\Admin\CalendarController              as ApiCalendarController;

// EMPLOYEE API Controllers
use App\Http\Controllers\Api\Employee\ProfileController           as ApiEmployeeProfileController;
use App\Http\Controllers\Api\Employee\AttendanceController        as EmpAttendanceApiController;
use App\Http\Controllers\Api\Employee\AttendanceRequestController as EmpAttendanceRequestApiController;
use App\Http\Controllers\Api\Employee\OvertimeRequestController   as EmpOvertimeRequestApiController;
use App\Http\Controllers\Api\Employee\LeaveRequestController      as EmpLeaveRequestApiController;
use App\Http\Controllers\Api\Employee\PayslipController           as EmpPayslipApiController;
use App\Http\Controllers\Api\Employee\PresensiController          as PresensiController;


// ... (semua use Controller seperti punyamu)

// ─────────────────────────────────────────────────────────────
// Public
// ─────────────────────────────────────────────────────────────
Route::post('login', [AuthController::class, 'login']);

// ─────────────────────────────────────────────────────────────
// Protected (Sanctum)
// ─────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth helpers
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me',     [AuthController::class, 'me'])->name('api.me'); // ← renamed

    /* =========================================================
     * ADMIN API (prefix: /api/admin)  → name: api.admin.*
     * NOTE: Spatie guard override: ',api'
     * =========================================================
     */
    Route::prefix('admin')->name('api.admin.')->group(function () {

        // Recruitment (HR scope)
        Route::middleware('permission:hr.employee.view_basic,api')->group(function () {
            Route::apiResource('recruitment', ApiRecruitmentController::class)->only(['index','store','show']);
        });

        // Employees (HR master)
        Route::middleware('permission:hr.employee.view_basic,api')->group(function () {
            Route::apiResource('employees', ApiEmployeeController::class);
        });

        // Attendance (view)
        Route::middleware('permission:hr.attendance.view,api')->group(function () {
            Route::get('attendance',              [ApiAdminAttendanceController::class,'index']);
            Route::get('attendance/{attendance}', [ApiAdminAttendanceController::class,'show']);
            Route::get('attendance-summary',      [ApiAttendanceSummaryController::class,'index']);
            Route::apiResource('calendars', ApiCalendarController::class)->only(['index','store','show','update','destroy']);
        });

        // Attendance/Overtime/Leave approval (approve/reject)
        Route::middleware('permission:hr.attendance.approve,api')->group(function () {

            // Attendance requests (approve/reject via PATCH)
            Route::get('attendance-requests',                                [ApiAdminAttendanceRequestController::class,'index']);
            Route::patch('attendance-requests/{attendance_request}/approve', [ApiAdminAttendanceRequestController::class,'approve']);
            Route::patch('attendance-requests/{attendance_request}/reject',  [ApiAdminAttendanceRequestController::class,'reject']);

            // Overtime requests (resource + approve/reject via PATCH)
            Route::apiResource('overtime-requests', ApiAdminOvertimeRequestController::class)
                 ->only(['index','store','show','update','destroy']);
            Route::patch('overtime-requests/{overtime_request}/approve',     [ApiAdminOvertimeRequestController::class,'approve'])
                 ->name('overtime-requests.approve');
            Route::patch('overtime-requests/{overtime_request}/reject',      [ApiAdminOvertimeRequestController::class,'reject'])
                 ->name('overtime-requests.reject');

            // Leave requests (approve/reject via PATCH)
            Route::get('leave-requests',                                     [ApiAdminLeaveRequestController::class,'index']);
            Route::patch('leave-requests/{leave_request}/approve',           [ApiAdminLeaveRequestController::class,'approve']);
            Route::patch('leave-requests/{leave_request}/reject',            [ApiAdminLeaveRequestController::class,'reject']);

            // Overtime settings (manage)
            Route::get('setting-overtime', [ApiSettingOvertimesController::class,'edit']);
            Route::put('setting-overtime', [ApiSettingOvertimesController::class,'update']);
        });

        // Payroll master (components/rates/adjustments/accounts)
        Route::middleware('permission:payroll.rate.manage,api')->group(function () {
            Route::apiResource('tunjangan', ApiTunjanganController::class);
            Route::apiResource('potongan',  ApiPotonganController::class);
            Route::apiResource('company-bank-accounts', ApiCompanyBankAccountController::class);
            Route::apiResource('employee-allowances',   ApiEmployeeAllowanceController::class);
            Route::apiResource('employee-deductions',   ApiEmployeeDeductionController::class);
        });

        // Organization (ops system-admin)
        Route::middleware('role:system-admin|super-admin,api')->group(function () {
            Route::apiResource('departments', ApiDepartmentController::class);
            Route::apiResource('positions',   ApiPositionController::class);
        });

        // Shifts & shift groups (HR scheduling)
        Route::middleware('permission:hr.attendance.approve,api')->group(function () {
            Route::apiResource('shifts',       ApiShiftController::class)->only(['index','store','update','destroy']);
            Route::apiResource('shift-groups', ApiShiftGroupController::class);
        });

        // Payroll run (view/simulate/finalize analog di API)
        Route::middleware('permission:payroll.run.view,api')->group(function () {
            Route::get('payroll',           [ApiPayrollController::class,'index']);
            Route::get('payroll/{payroll}', [ApiPayrollController::class,'show']);
        });
        Route::middleware('permission:payroll.run.simulate,api')->group(function () {
            Route::post('payroll',   [ApiPayrollController::class,'store']);
            Route::delete('payroll', [ApiPayrollController::class,'destroyAll']);
        });
        Route::middleware('permission:payroll.run.finalize,api')->group(function () {
            Route::patch('payroll/{payroll}/approve', [ApiPayrollController::class,'approve']);
        });
    });

    /* =========================================================
     * EMPLOYEE API (prefix: /api/employee) → name: api.employee.*
     * =========================================================
     */
    Route::prefix('employee')->name('api.employee.')->group(function () {
        // Profile
        Route::get('profile', [ApiEmployeeProfileController::class, 'show']);

        // Attendance (self)
        Route::get('attendance',         [EmpAttendanceApiController::class, 'index']);
        Route::post('attendance',        [EmpAttendanceApiController::class, 'store']);
        Route::get('attendance/history', [EmpAttendanceApiController::class, 'history']);

        // Presensi (jika controller terpisah)
        Route::post('presensi', [PresensiController::class, 'store']);

        // Attendance requests (self)
        Route::apiResource('presensi/requests', EmpAttendanceRequestApiController::class)
              ->only(['index','store','show']);

        // Overtime request (self)
        Route::get('overtime-request',         [EmpOvertimeRequestApiController::class, 'index']);
        Route::post('overtime-request',        [EmpOvertimeRequestApiController::class, 'store']);
        Route::get('overtime-request/history', [EmpOvertimeRequestApiController::class, 'history']);

        // Leave (self)
        Route::apiResource('cuti', EmpLeaveRequestApiController::class)->only(['index','store','show']);
        Route::get('cuti/history', [EmpLeaveRequestApiController::class, 'history']);

        // Payslip (self)
        Route::middleware('permission:payroll.payslip.view_self,api')->group(function () {
            Route::get('payslip/history',       [EmpPayslipApiController::class, 'history']);
            Route::get('payslip',               [EmpPayslipApiController::class, 'index']);
            Route::get('payslip/{payroll}',     [EmpPayslipApiController::class, 'show']);
            Route::get('payslip/{payroll}/pdf', [EmpPayslipApiController::class, 'downloadPdf']);
        });
    });
});
