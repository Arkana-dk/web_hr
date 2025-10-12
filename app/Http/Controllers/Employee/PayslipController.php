<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\Employee;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class PayslipController extends Controller
{
    public function index()
    {
        $employee = $this->getLoggedInEmployee();

        $payrolls = Payroll::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->orderByDesc('start_date')
            ->get();

        return view('employee.pages.payslip-index', compact('payrolls'));
    }

public function show(Payroll $payroll)
{
    $employee = $this->getLoggedInEmployee();

    abort_unless(
        $payroll->employee_id == $employee->id && $payroll->status === 'approved',
        403,
        'Anda tidak berhak mengakses payslip ini.'
    );

    $payroll->load('details');

    $payroll_month = $payroll->start_date->format('F Y'); // Contoh: "July 2025"

    return view('employee.pages.payslip-show', compact('payroll', 'payroll_month'));
}


   public function pdf(Payroll $payroll)
    {
        $employee = $this->getLoggedInEmployee();

        abort_unless(
            $payroll->employee_id === $employee->id && $payroll->status === 'approved',
            403,
            'Anda tidak berhak mengunduh payslip ini.'
        );

        $payroll->load('details', 'employee');

        $pdf = Pdf::loadView('employee.pages.payslip-pdf', compact('payroll'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("payslip-{$payroll->start_date->format('Y-m')}.pdf");
    }

    /**
     * Ambil employee berdasarkan user yang login.
     */
    protected function getLoggedInEmployee()
    {
        return Employee::where('user_id', Auth::id())->firstOrFail();
    }
}
