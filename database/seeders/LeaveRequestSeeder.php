<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveRequest;
use App\Models\Employee;
use Carbon\Carbon;

class LeaveRequestSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::take(2)->get();

        foreach ($employees as $employee) {
            LeaveRequest::create([
                'employee_id'   => $employee->id,
                'type'          => 'sakit', // bisa juga 'izin', 'cuti', dll
                'start_date'    => Carbon::now()->subDays(1)->format('Y-m-d'),
                'end_date'      => Carbon::now()->addDays(2)->format('Y-m-d'),
                'reason'        => 'Seeder testing - sakit',
                'status'        => 'pending',
                
            ]);
        }
    }
}
