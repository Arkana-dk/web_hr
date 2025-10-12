<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrgStructureSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Departments & Sections
        $org = [
            'Human Resources' => ['Recruitment', 'Payroll', 'General Affairs'],
            'Finance'         => ['Accounting', 'Treasury', 'Tax'],
            'Engineering'     => ['Backend', 'Frontend', 'QA'],
            'Operations'      => ['Warehouse', 'Logistics', 'Customer Support'],
        ];

        $sectionsByName = [];
        foreach ($org as $deptName => $sectionNames) {
            $dept = \App\Models\Department::updateOrCreate(['name' => $deptName], []);
            foreach ($sectionNames as $secName) {
                $sec = \App\Models\Section::updateOrCreate(
                    ['name' => $secName, 'department_id' => $dept->id],
                    []
                );
                $sectionsByName[$secName] = $sec->id;
            }
        }

        // 2) Positions
        $positionNames = ['Staff', 'Senior Staff', 'Supervisor', 'Manager', 'Head'];
        $positionsByName = [];
        foreach ($positionNames as $posName) {
            $pos = \App\Models\Position::updateOrCreate(['name' => $posName], []);
            $positionsByName[$posName] = $pos->id;
        }

        // 3) Pivot position_section (attach 4 level posisi ke semua section)
        $attachPositions = ['Staff', 'Senior Staff', 'Supervisor', 'Manager'];
        foreach ($sectionsByName as $sectionId) {
            foreach ($attachPositions as $posName) {
                DB::table('position_section')->updateOrInsert(
                    ['position_id' => $positionsByName[$posName], 'section_id' => $sectionId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // (Opsional) satu “Head” per department: pakai section pertama
        foreach ($org as $sectionNames) {
            if (count($sectionNames) > 0) {
                $firstSection = $sectionNames[0];
                DB::table('position_section')->updateOrInsert(
                    ['position_id' => $positionsByName['Head'], 'section_id' => $sectionsByName[$firstSection]],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }
}
