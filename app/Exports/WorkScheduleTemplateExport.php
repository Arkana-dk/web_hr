<?php

namespace App\Exports;

use App\Exports\Sheets\WorkScheduleInstructionsSheet;
use App\Exports\Sheets\WorkScheduleMatrixSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class WorkScheduleTemplateExport implements WithMultipleSheets
{
    public function __construct(
        public string $month,
        public array  $meta,
        public $employees, // Collection<Employee>
        public $shifts,    // Collection<Shift>
       
    ) {}

    public function sheets(): array
    {
        return [
            new WorkScheduleInstructionsSheet(
                month: $this->month,
                meta: $this->meta,
                employees: $this->employees,
                shifts: $this->shifts,
                
            ),
            new WorkScheduleMatrixSheet(
                month: $this->month,
                meta: $this->meta,
                employees: $this->employees,
                shifts: $this->shifts,
               
            ),
        ];
    }
}
