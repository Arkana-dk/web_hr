<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Role, Permission};
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Paksa gunakan 'web' agar deterministik
        $guard = 'web';

     $all = [
        // HR (attendance & org)
        'hr.employee.view_basic','hr.employee.view_sensitive','hr.attendance.view','hr.attendance.approve',
        'attendance-summary.view','transport.setting.manage','attendance.location.manage',
        'work-schedule.manage','shift.manage','shift-change.request.approve','org.manage',

        // Attendance (opsional jika mau di-guard per permission)
        'attendance.checkin','attendance.checkout','attendance.view.self',

        // Requests
        'attendance-request.create','attendance-request.view.self','attendance-request.approve',
        'overtime-request.create','overtime-request.view.self','overtime-request.approve',
        'leave-request.create','leave-request.view.self','leave-request.approve',
        'shift-change.request.create', // <— ditambahkan

        // Payroll
        'payroll.run.view','payroll.run.create','payroll.run.review','payroll.run.simulate',
        'payroll.run.finalize','payroll.run.reopen',
        'payroll.run.adjustment.manage','payroll.export',
        'payroll.group.manage','payroll.group-component.manage','payroll.component.manage','payroll.rate.manage',
        'payroll.payslip.view_self','payroll.payslip.view_all',

        // Administration Access (untuk modul #15)
        'admin.user.manage','admin.role.manage','admin.permission.manage',

        'user.role.manage'
    ];


                // Buat permission-nya dulu
                foreach ($all as $p) { Permission::firstOrCreate(['name'=>$p,'guard_name'=>$guard]); }


        // definisi role + matriks permission
       // Matrix role -> permission (tanpa payroll di 'admin')
       // === ROLES → PERMISSIONS ===
            $matrix = [
                'employee' => [
                    'attendance.checkin','attendance.checkout','attendance.view.self',
                    'attendance-request.create','attendance-request.view.self',
                    'overtime-request.create','overtime-request.view.self',
                    'leave-request.create','leave-request.view.self',
                    'shift-change.request.create',
                    'payroll.payslip.view_self',
                ],

                // HR-STAFF: sesuai requirement → tambah schedule, bisa lihat & approve attendance
                'hr-staff' => [
                    'work-schedule.manage',
                    'hr.attendance.view',
                    'hr.attendance.approve',
                    'attendance-summary.view',
                    // TIDAK ada hr.employee.manage / org.manage / shift.manage
                ],

                // HR-ADMIN: lebih luas dalam lingkup HR
                'hr-admin' => [
                    // semua milik staff:
                    'work-schedule.manage','hr.attendance.view','hr.attendance.approve','attendance-summary.view',
                    // ekstra:
                    'hr.employee.view_basic','hr.employee.view_sensitive','hr.employee.manage',
                    'org.manage','shift.manage','shift-change.request.approve',
                    'attendance.location.manage','transport.setting.manage',
                    'attendance-request.approve','overtime-request.approve','leave-request.approve',
                ],

                // Payroll roles
                'payroll-staff' => [
                    'payroll.run.view','payroll.run.create','payroll.run.review','payroll.run.simulate',
                    'payroll.run.adjustment.manage','payroll.export',
                    'payroll.group.manage','payroll.group-component.manage','payroll.component.manage','payroll.rate.manage',
                    'payroll.payslip.view_all',
                ],
                'payroll-admin' => [
                    'payroll.run.view','payroll.run.create','payroll.run.review','payroll.run.simulate',
                    'payroll.run.finalize','payroll.run.reopen',
                    'payroll.run.adjustment.manage','payroll.export',
                    'payroll.group.manage','payroll.group-component.manage','payroll.component.manage','payroll.rate.manage',
                    'payroll.payslip.view_all',
                ],

                // System admin (non-payroll)
                'system-admin' => [
                    'admin.user.manage','admin.role.manage','admin.permission.manage','org.manage',
                ],

                'super-admin' => ['*'],
                'user'        => [],
            ];

            // HR admin boleh kelola user role, super-admin juga
            $matrix['hr-admin'][]    = 'user.role.manage';
            $matrix['super-admin'][] = 'user.role.manage';




        foreach ($matrix as $roleName => $allowed) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);

            // Expand wildcard manual (enable_wildcard_permission = false, jadi kita expand via query)
            $expanded = collect($allowed)->flatMap(function ($p) use ($guard) {
                if (str_ends_with($p, '*')) {
                    return Permission::where('guard_name', $guard)
                        ->where('name', 'like', str_replace('*', '%', $p))
                        ->pluck('name')
                        ->all();
                }
                return [$p];
            })->unique()->values()->all();

            $role->syncPermissions($expanded);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
