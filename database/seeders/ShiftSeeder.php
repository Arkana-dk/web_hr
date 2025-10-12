<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Shift;

class ShiftSeeder extends Seeder
{
    public function run()
    {
        $shifts = [
            ['id' => 1,  'name' => '[1#0] IZIN',            'start_time' => '00:00:00', 'end_time' => '00:00:00'],
            ['id' => 2,  'name' => '[2#0] IZIN DIPOTONG',   'start_time' => '00:00:00', 'end_time' => '00:00:00'],
            ['id' => 3,  'name' => '[3#0] CUTI',            'start_time' => '00:00:00', 'end_time' => '00:00:00'],
            ['id' => 4,  'name' => '[4#0] CUTI BERSAMA',    'start_time' => '00:00:00', 'end_time' => '00:00:00'],
            ['id' => 5,  'name' => '[5#0] LIBUR',           'start_time' => '00:00:00', 'end_time' => '00:00:00'],
            ['id' => 6,  'name' => '[6#0] LIBUR NASIONAL',  'start_time' => '00:00:00', 'end_time' => '00:00:00'],
            ['id' => 7,  'name' => '[7#1] NS',           'start_time' => '08:00:00', 'end_time' => '16:45:00'],
            ['id' => 8,  'name' => '[8#1] S 1',          'start_time' => '08:10:00', 'end_time' => '16:10:00'],
            ['id' => 9,  'name' => '[9#1] S 2',          'start_time' => '16:10:00', 'end_time' => '00:10:00'],
            ['id' => 10, 'name' => '[10#1] S 3',          'start_time' => '23:47:00', 'end_time' => '08:10:00'],
        ];

        foreach ($shifts as $shift) {
            DB::table('shifts')->updateOrInsert(['id' => $shift['id']], $shift);
        }
    }
}


