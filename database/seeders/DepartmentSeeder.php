<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        $departments = [
            'IT',
            'Human Resources',
            'Finance',
            'Marketing',
            'Sales',
            'Operations',
            'Legal',
            'Customer Service',
            'Procurement',
            'R&D',
        ];

        foreach ($departments as $name) {
            Department::firstOrCreate(['name' => $name]);
        }
    }
}
