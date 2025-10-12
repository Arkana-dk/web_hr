<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UsersAndEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        // ===== 1) ADMIN USER =====
        $admin = \App\Models\User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Administrator',
                'role'     => 'admin',                  // enum: user|admin|superadmin
                'password' => Hash::make('admin123'),  // password: admin123
            ]
        );

        // ===== 2) REFERENSI ORG =====
        // Ambil yang spesifik kalau ada, kalau tidak ambil yang pertama
        $dept  = \App\Models\Department::where('name', 'Engineering')->first() ?: \App\Models\Department::first();
        $sect  = \App\Models\Section::where('name', 'Backend')->first() ?: \App\Models\Section::first();
        $pos   = \App\Models\Position::where('name', 'Staff')->first() ?: \App\Models\Position::first();
        $group = \DB::table('groups')->where('code', 'HO')->first() ?: \DB::table('groups')->first();

        // ===== 3) USER REGULER (ALICE & BOB) =====
        $uAlice = \App\Models\User::updateOrCreate(
            ['email' => 'alice@example.com'],
            [
                'name'     => 'Alice',
                'role'     => 'user',
                'password' => Hash::make('password'),
            ]
        );

        $uBob = \App\Models\User::updateOrCreate(
            ['email' => 'bob@example.com'],
            [
                'name'     => 'Bob',
                'role'     => 'user',
                'password' => Hash::make('password'),
            ]
        );

        // ===== 4) EMPLOYEES (isi kolom wajib + beberapa opsional) =====
        $empCols = Schema::getColumnListing('employees');

        $makeEmp = function ($user, array $meta) use ($empCols, $dept, $sect, $pos, $group) {
            $payload = [
                'user_id'                  => $user->id,
                'role'                     => 'employee', // enum employees: employee|admin|superadmin|developer
                'name'                     => $meta['name'],
                'national_identity_number' => $meta['nik'],
                'family_number_card'       => $meta['kk'],
                'email'                    => $user->email,
                'gender'                   => $meta['gender'], // 'Laki-laki' | 'Perempuan'
                'phone'                    => $meta['phone'] ?? null,
                'address'                  => $meta['address'] ?? null,
                'salary'                   => $meta['salary'] ?? 0,
            ];

            // kolom opsional (diisi hanya jika ada di schema)
            if ($dept && in_array('department_id', $empCols)) $payload['department_id'] = $dept->id;
            if ($sect && in_array('section_id', $empCols))     $payload['section_id']    = $sect->id;
            if ($pos && in_array('position_id', $empCols))     $payload['position_id']   = $pos->id;
            if ($group && in_array('group_id', $empCols))      $payload['group_id']      = $group->id;

            if (in_array('employee_number', $empCols))         $payload['employee_number'] = $meta['emp_no'];
            if (in_array('marital_status', $empCols))          $payload['marital_status']  = $meta['marital_status'] ?? null;
            if (in_array('date_of_birth', $empCols))           $payload['date_of_birth']   = $meta['dob'] ?? null;
            if (in_array('place_of_birth', $empCols))          $payload['place_of_birth']  = $meta['pob'] ?? null;
            if (in_array('tmt', $empCols))                     $payload['tmt']             = now()->toDateString();

            // Upsert by user_id
            \App\Models\Employee::updateOrCreate(['user_id' => $user->id], $payload);
        };

        // Alice
        $makeEmp($uAlice, [
            'name'      => 'Alice',
            'nik'       => '3201020101010001',
            'kk'        => '3201022202020001',
            'gender'    => 'Perempuan',
            'phone'     => '081234567890',
            'address'   => 'Jl. Mawar No. 1',
            'salary'    => 5000000,
            'emp_no'    => 'EMP0001',
            'marital_status' => 'Belum Kawin',
            'dob'       => '1996-01-01',
            'pob'       => 'Bandung',
        ]);

        // Bob
        $makeEmp($uBob, [
            'name'      => 'Bob',
            'nik'       => '3201020101010002',
            'kk'        => '3201022202020002',
            'gender'    => 'Laki-laki',
            'phone'     => '081298765432',
            'address'   => 'Jl. Melati No. 2',
            'salary'    => 4800000,
            'emp_no'    => 'EMP0002',
            'marital_status' => 'Sudah Kawin',
            'dob'       => '1994-02-02',
            'pob'       => 'Jakarta',
        ]);
    }
}
