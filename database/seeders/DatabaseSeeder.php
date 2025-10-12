<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PayrollSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // (Optional) Buat user test
        \App\Models\User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Panggil seeder lain
        $this->call([
            RoleUserSeeder::class,
           
            AttendanceSeeder::class,
            AttendanceRequestSeeder::class,
            LeaveRequestSeeder::class,
            PayrollSeeder::class,
            ShiftSeeder::class,
            PayComponentsSeeder::class,
            PayComponentsStarterSeeder::class, // Jangan dipakai! ini Beta Test awal payroll
            PayComponentSeeder::class,
            
            
            UsersAndEmployeesSeeder::class,
            BackfillLegacyRolesSeeder::class,
            DepartmentSectionPositionSeeder :: class,


            // Coba Seeder 24/08/2025
            PayComponentsAndRatesSeeder::class,

            // Overtime Seeder test 06/09/2025
            OvertimeSeeder::class,


            // Data Employee Untuk test payroll
            EmployeeSeeder::class,

            // Coba Attendance Seeder Baru
            CobaAttendanceSeeder::class,

            // Seed Untuk Tipe Cuti
            LeaveTypeSeeder::class
            
        ]);
    }
}
