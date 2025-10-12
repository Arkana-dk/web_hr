<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Payroll;

class PayslipPolicy
{
    /**
     * Determine if the given payroll can be viewed.
     */
    public function view(User $user, Payroll $payroll)
    {
        // Hanya user/employee yang punya hubungan dengan payslip ini
        return (
            $user->hasAnyRole(['user','employee']) &&
            $user->id === $payroll->employee_id
        );
    }

    /**
     * Determine if the given payroll PDF can be downloaded.
     */
    public function downloadPdf(User $user, Payroll $payroll)
    {
        // Sama aturan dengan view
        return $this->view($user, $payroll);
    }
}
