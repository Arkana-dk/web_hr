<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayslipController extends Controller
{
    /**
     * GET  /api/employee/payslip
     * Return list of approved payrolls for the authenticated employee.
     */
    public function index()
    {
        $employee = Employee::where('user_id', Auth::id())->firstOrFail();

        $payrolls = Payroll::where('employee_id', $employee->id)
            ->where('status','approved')
            ->orderBy('start_date','desc')
            ->get();

        return response()->json([
            'data' => $payrolls->map(fn($p) => [
                'id'           => $p->id,
                'period'       => $p->start_date->format('Y-m'),
                'basic_salary' => $p->basic_salary,
                'allowances'   => $p->total_allowances,
                'deductions'   => $p->total_deductions,
                'overtime'     => $p->overtime_amount,
                'net_salary'   => $p->net_salary,
            ]),
        ]);
    }

    /**
     * GET  /api/employee/payslip/{payroll}
     * Return detail of one approved payroll.
     */
    public function show(Payroll $payroll)
    {
        $employee = Employee::where('user_id', Auth::id())->firstOrFail();

        // authorize
        if ($payroll->employee_id !== $employee->id || $payroll->status !== 'approved') {
            return response()->json(['message'=>'Forbidden'], 403);
        }

        $payroll->load('details');

        return response()->json([
            'id'                 => $payroll->id,
            'period'            => $payroll->start_date->format('Y-m'),
            'basic_salary'      => $payroll->basic_salary,
            'total_allowances'  => $payroll->total_allowances,
            'total_deductions'  => $payroll->total_deductions,
            'overtime_amount'   => $payroll->overtime_amount,
            'net_salary'        => $payroll->net_salary,
            'details'           => $payroll->details->map(fn($d) => [
                'type'   => $d->component_type,
                'name'   => $d->component_name,
                'amount' => $d->amount,
            ]),
        ]);
    }

    /**
     * GET  /api/employee/payslip/{payroll}/pdf
     * Stream-down a PDF version of the payslip.
     */
    public function downloadPdf(Payroll $payroll)
    {
        $employee = Employee::where('user_id', Auth::id())->firstOrFail();

        if ($payroll->employee_id !== $employee->id || $payroll->status !== 'approved') {
            return response()->json(['message'=>'Forbidden'], 403);
        }

        $payroll->load('details','employee');

        $pdf = Pdf::loadView('employee.pages.payslip-pdf', compact('payroll'))
                  ->setPaper('a4', 'portrait');

        // returns a binary PDF response
        return $pdf->download("payslip-{$payroll->start_date->format('Y-m')}.pdf");
    }

    /**
     * GET /api/employee/payslip/history
     * Return paginated payroll history (optionally filtered by month/year).
     */
    public function history(Request $request)
    {
        $employee = Employee::where('user_id', Auth::id())->firstOrFail();

        $query = Payroll::where('employee_id', $employee->id)
                        ->where('status', 'approved')
                        ->orderByDesc('payment_date');

        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('payment_date', $request->month)
                  ->whereYear('payment_date', $request->year);
        }

        $payrolls = $query->paginate(10); // Or ->get() if no pagination

        return response()->json([
            'data' => $payrolls->items(),
            'meta' => [
                'current_page' => $payrolls->currentPage(),
                'last_page'    => $payrolls->lastPage(),
                'per_page'     => $payrolls->perPage(),
                'total'        => $payrolls->total(),
            ],
        ]);
    }
}
// End of PayslipController.php
// --- IGNORE ---