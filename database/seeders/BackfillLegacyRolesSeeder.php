<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class BackfillLegacyRolesSeeder extends Seeder {
  public function run(): void {
    // pastikan role untuk 2 guard ada
    foreach (['web','api'] as $guard) {
      foreach (['super-admin','system-admin','hr-admin','hr-staff','payroll-admin','payroll-staff','employee'] as $r) {
        Role::findOrCreate($r, $guard);
      }
    }

    $map = ['superadmin'=>'super-admin','admin'=>'system-admin','user'=>'employee'];

    User::query()->select(['id','role'])->whereNotNull('role')
      ->chunkById(200, function($users) use ($map) {
        foreach ($users as $u) {
          $target = $map[$u->role] ?? 'employee';
          // assign ke web
          $u->syncRoles([Role::findByName($target, 'web')]);
          // assign juga ke api (jika API kamu pakai guard api)
          try { $u->assignRole(Role::findByName($target, 'api')); } catch (\Throwable $e) {}
        }
      });
  }
}
