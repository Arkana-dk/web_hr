<?php

namespace App\Domain\Payroll\Rules;

use App\Domain\Payroll\Rules\Contracts\PayrollRule;
use App\Models\Employee;

class Pph21Rule implements PayrollRule
{
    public function buildLines($ctx, Employee $employee, array $currentLines): array
    {
        \Log::debug('RULE_ENTER', ['rule' => __CLASS__, 'emp_id' => $employee->id]);

        return [[
            'componentCode' => 'PPH21',
            'componentType' => 'tax',
            'side'          => 'deduction',
            'name'          => 'PPh21 (Dummy)',
            'amount'        => -12345, // dummy untuk test
            'source'        => 'Pph21Rule/test',
        ]];
    }
}

