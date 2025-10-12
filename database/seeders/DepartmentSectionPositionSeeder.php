<?php
// File: database/seeders/DepartmentSectionPositionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\{Department, Section, Position};

class DepartmentSectionPositionSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'Operations'        => ['Produksi A', 'Produksi B', 'Maintenance', 'Quality Control'],
            'Human Resources'   => ['Recruitment', 'Training & Development', 'Payroll'],
            'Finance'           => ['Accounting', 'Budgeting', 'Tax'],
            'IT'                => ['Infrastructure', 'Development', 'Support'],
            'Sales & Marketing' => ['Domestic Sales', 'International Sales', 'Marketing'],
        ];

        $positions = [
            // Umum
            'Operator', 'Supervisor', 'Line Leader', 'Administrator',
            // Quality / Ops
            'QA Inspector', 'Operations Manager',
            // HR
            'HR Staff', 'HR Supervisor', 'HR Manager',
            // Finance
            'Accountant', 'Finance Analyst', 'Finance Manager',
            // IT
            'System Administrator', 'Software Developer', 'IT Support', 'IT Manager',
            // Sales/Marketing
            'Sales Executive', 'Key Account', 'Sales Admin', 'Marketing Specialist', 'Sales Manager',
        ];

        // Pastikan semua posisi ada
        $positionModels = collect($positions)->map(fn ($name) => Position::firstOrCreate(['name' => $name]));

        foreach ($departments as $deptName => $sectionNames) {
            $dept = Department::firstOrCreate(['name' => $deptName]);

            // Buat section untuk department tsb
            $sections = collect($sectionNames)->map(fn ($s) => Section::firstOrCreate([
                'name'          => $s,
                'department_id' => $dept->id,
            ]));

            // Heuristik lampiran posisi ke setiap section
            foreach ($sections as $section) {
                $attachIds = [];

                // Random 2–5 posisi agar bervariasi
                $randCount = min(5, max(2, $positionModels->count()));
                $rand      = $positionModels->random($randCount);
                $attachIds = array_merge($attachIds, $rand->pluck('id')->all());

                // Tambahkan posisi yang relevan berdasarkan nama section
                $sn = Str::lower($section->name);
                foreach ($positionModels as $p) {
                    $pn = Str::lower($p->name);

                    if (Str::contains($sn, 'quality') && Str::contains($pn, 'qa')) {
                        $attachIds[] = $p->id;
                    }
                    if (Str::contains($sn, 'maintenance') && (Str::contains($pn, 'manager') || Str::contains($pn, 'administrator'))) {
                        $attachIds[] = $p->id;
                    }
                    if (Str::contains($sn, 'development') && Str::contains($pn, 'developer')) {
                        $attachIds[] = $p->id;
                    }
                    if (Str::contains($sn, 'support') && (Str::contains($pn, 'support') || Str::contains($pn, 'administrator'))) {
                        $attachIds[] = $p->id;
                    }
                    if (Str::contains($sn, 'sales') && (Str::contains($pn, 'sales') || Str::contains($pn, 'marketing'))) {
                        $attachIds[] = $p->id;
                    }
                }

                $section->positions()->syncWithoutDetaching(array_values(array_unique($attachIds)));
            }
        }
    }
}
