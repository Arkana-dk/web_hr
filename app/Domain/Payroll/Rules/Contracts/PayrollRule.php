<?php

namespace App\Domain\Payroll\Rules\Contracts;

use App\Domain\Payroll\DTOs\CalcResult;

interface PayrollRule
{
    /**
     * @param  mixed $ctx  Context dari PayContextFactory
     * @param  \App\Models\Employee $employee
     * @param  CalcResult $result  (mutated)
     * @return void
     */
        public function apply($ctx, Employee $employee, CalcResult $result): void;

}
