<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\{Role, Permission};
use Spatie\Permission\PermissionRegistrar;

class PermissionsDualGuardSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan cache permission
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Guard yang didukung
        $guards = ['web', 'api'];

        // Daftar permission (HR & Payroll)
        $perms = [
            // HR
            'hr.employee.view_basic', 'hr.employee.view_sensitive',
            'hr.attendance.view', 'hr.attendance.approve',

            // Payroll
            'payroll.run.view', 'payroll.run.simulate', 'payroll.run.finalize', 'payroll.run.reopen',
            'payroll.rate.manage', 'payroll.adjustment.manage',
            'payroll.payslip.view_self', 'payroll.payslip.view_all',
        ];

        // Role → paket permission (boleh wildcard)
        $matrix = [
            'super-admin'   => $perms,
            'system-admin'  => ['hr.employee.view_basic','hr.attendance.view','payroll.run.view'],
            'hr-admin'      => ['hr.employee.*','hr.attendance.*'],
            'hr-staff'      => ['hr.employee.view_basic','hr.attendance.view'],
            'payroll-admin' => ['payroll.*'],
            'payroll-staff' => ['payroll.run.view','payroll.run.simulate','payroll.payslip.view_all'],
            'employee'      => ['payroll.payslip.view_self'],
        ];

        foreach ($guards as $guard) {
            // 1) Buat semua permission untuk guard ini
            foreach ($perms as $p) {
                Permission::firstOrCreate(['name' => $p, 'guard_name' => $guard]);
            }

            // 2) Buat role & assign permissions (expand wildcard)
            foreach ($matrix as $roleName => $allowed) {
                $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);

                $expanded = collect($allowed)->flatMap(function ($p) use ($guard) {
                    if (Str::endsWith($p, '*')) {
                        // 'hr.employee.*' -> 'hr.employee.%'
                        $like = str_replace('*', '%', $p);
                        return Permission::where('guard_name', $guard)
                            ->where('name', 'like', $like)
                            ->pluck('name')
                            ->all();
                    }
                    return [$p];
                })->unique()->values()->all();

                $role->syncPermissions($expanded);
            }
        }

        // Bersihkan cache lagi setelah update
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
