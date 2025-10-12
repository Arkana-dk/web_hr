<?php

namespace App\Exports\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class WorkScheduleInstructionsSheet implements FromView, WithTitle
{
    public function __construct(
        public string $month,
        public array  $meta,
        public $employees,
        public $shifts,
        
    ) {}

    public function view(): View
    {
        return view('admin.pages.work-schedules.export_excel_instructions', [
            'month'        => $this->month,
            'meta'         => $this->meta,
            'employees'    => $this->employees,
            'shifts'       => $this->shifts,
            
        ]);
    }

    public function title(): string
    {
        return 'INSTRUKSI';
    }
}
