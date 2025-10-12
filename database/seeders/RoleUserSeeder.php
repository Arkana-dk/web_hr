<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RoleUserSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

        // Pastikan semua role yang kita gunakan tersedia
        $roles = [
            'super-admin',
            'system-admin',
            'hr-admin',
            'hr-staff',
            'payroll-admin',
            'payroll-staff',
            'employee',
            'user', // dummy/testing
        ];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => $guard]);
        }

        $users = [
            // Tetap: super admin
            [
                'name' => 'super admin',
                'email' => 'superadmin@gmail.com',
                'password' => 'poweradmin',
                'roles' => ['super-admin'],
            ],

            // Ubah: admin lama diarahkan ke role payroll-admin (biar tetap bisa akses modul payroll)
            [
                'name' => 'admin',
                'email' => 'admin@gmail.com',
                'password' => 'admin123',
                'roles' => ['payroll-admin'],
            ],

            // Baru: payroll staff (untuk simulasi/review tanpa finalize)
            [
                'name' => 'payroll staff',
                'email' => 'payrollstaff@gmail.com',
                'password' => 'payrollstaff123',
                'roles' => ['payroll-staff'],
            ],

            // Baru: HR admin & HR staff
            [
                'name' => 'hr admin',
                'email' => 'hradmin@gmail.com',
                'password' => 'hradmin123',
                'roles' => ['hr-admin'],
            ],
            [
                'name' => 'hr staff',
                'email' => 'hrstaff@gmail.com',
                'password' => 'hrstaff123',
                'roles' => ['hr-staff'],
            ],

            // Baru: system admin (manajemen user/role/permission; non-payroll)
            [
                'name' => 'system admin',
                'email' => 'systemadmin@gmail.com',
                'password' => 'systemadmin123',
                'roles' => ['system-admin'],
            ],

            // Tetap: employee demo
            [
                'name' => 'employee',
                'email' => 'employee@gmail.com',
                'password' => 'employee123',
                'roles' => ['employee'],
            ],

            // Tetap: dummy lokal
            [
                'name' => '123',
                'email' => '123',
                'password' => '123',
                'roles' => ['user'],
            ],
        ];

        foreach ($users as $u) {
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name'     => $u['name'],
                    'password' => Hash::make($u['password']),
                ]
            );

            // Assign roles by name (guard 'web')
            $user->syncRoles($u['roles']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
