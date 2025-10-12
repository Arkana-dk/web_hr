<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class WipeDevDataSeeder extends Seeder
{
    public function run(): void
    {
        $tables = [
            // HR / Attendance
            'attendances','attendance_requests','overtime_requests','leave_requests',
            'shift_change_requests','attendance_location_settings',
            // Schedule
            'work_schedules','shifts',
            // Org
            'departments','groups','sections','positions',
            // Payroll
            'pay_runs','pay_groups','pay_group_components',
            'pay_components','pay_component_rates',
            // Lain
            'transport_routes','company_bank_accounts',
            'employee_allowances','employee_deductions',
        ];

        Schema::disableForeignKeyConstraints();
        foreach ($tables as $t) {
            if (Schema::hasTable($t)) DB::table($t)->truncate();
        }
        Schema::enableForeignKeyConstraints();

        // (opsional) reset cache permission
        if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
