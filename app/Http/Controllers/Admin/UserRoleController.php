<?php
// app/Http/Controllers/Admin/UserRoleController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    // Role yang HANYA boleh diatur oleh HR-Admin (harus ada di DB.guard=web)
    private array $hrAssignable = ['hr-staff','employee'];

    public function index(Request $req)
    {
        $actor = $req->user();

        // daftar role untuk filter di UI (Super-Admin: semua; HR-Admin: terbatas)
        $allRoles = $actor->hasRole('super-admin')
            ? Role::where('guard_name','web')->pluck('name')->all()
            : $this->hrAssignable;

        // base query user
        $q = User::query()->with('roles');

        // HR-Admin hanya boleh melihat user yang seluruh rolenya ⊆ hrAssignable
        if ($actor->hasRole('hr-admin') && !$actor->hasRole('super-admin')) {
            $q->whereDoesntHave('roles', function($r){
                $r->whereNotIn('name', $this->hrAssignable);
            });
        }

        // --- FIX: hindari undefined variable ---
        $s = $req->get('q');
        $filterRole = $req->get('role');

        // search/filter
        if ($s) {
            $q->where(fn($w)=> $w->where('name','like',"%$s%")->orWhere('email','like',"%$s%"));
        }
        if ($filterRole) {
            $q->whereHas('roles', fn($r)=> $r->where('name',$filterRole));
        }

        $users = $q->orderBy('name')->paginate(12)->withQueryString();

        return view('admin.pages.user-roles.index', [
            'users'    => $users,
            'allRoles' => $allRoles,
            'filter'   => ['q'=>$s,'role'=>$filterRole],
        ]);
    }

    public function edit(User $user, Request $req)
    {
        $actor = $req->user();

        $this->authorizeTarget($actor, $user); // lempar 403 jika tak boleh

        $assignable = $actor->hasRole('super-admin')
            ? Role::where('guard_name','web')->pluck('name','name')->all()
            : collect($this->hrAssignable)->mapWithKeys(fn($r)=>[$r=>$r])->all();

        return view('admin.pages.user-roles.edit', [
            'user'       => $user->load('roles'),
            'assignable' => $assignable,
        ]);
    }

public function update(User $user, Request $req)
{
    $actor = $req->user();
    $this->authorizeTarget($actor, $user);

    // --- Ambil dan normalkan input ---
    $requestedRoles = array_values(array_unique((array)$req->input('roles', [])));

    // --- Tambahan validasi email & password ---
    $validated = $req->validate([
        'email' => 'required|email|unique:users,email,' . $user->id,
        'password' => 'nullable|string|min:6|confirmed',
    ]);

    // --- Tentukan roles yang boleh di-assign oleh aktor ---
    if ($actor->hasRole('super-admin')) {
        $assignable = \Spatie\Permission\Models\Role::where('guard_name', 'web')->pluck('name')->all();
    } else {
        $assignable = $this->hrAssignable;
    }

    // --- Validasi role yang diajukan ---
    if (empty($requestedRoles)) {
        return back()->withErrors(['roles' => 'Minimal pilih satu role.'])->withInput();
    }

    $validInDb = \Spatie\Permission\Models\Role::where('guard_name', 'web')
        ->whereIn('name', $requestedRoles)
        ->pluck('name')
        ->all();

    foreach ($requestedRoles as $r) {
        if (!in_array($r, $assignable, true)) {
            abort(403, "Anda tidak boleh menetapkan role [$r].");
        }
        if (!in_array($r, $validInDb, true)) {
            abort(422, "Role [$r] tidak ditemukan.");
        }
    }

    // --- Aturan khusus: HR-Admin tidak boleh ubah dirinya sendiri ---
    if ($actor->id === $user->id && $actor->hasRole('hr-admin') && !$actor->hasRole('super-admin')) {
        return back()->withErrors(['roles' => 'Anda tidak dapat mengubah role akun Anda sendiri.'])->withInput();
    }

    return \DB::transaction(function() use ($user, $requestedRoles, $validated) {
        // --- Cegah hapus super-admin terakhir ---
        if ($user->hasRole('super-admin')) {
            $roleSA = \Spatie\Permission\Models\Role::where('name','super-admin')->where('guard_name','web')->first();
            $totalSA = $roleSA ? $roleSA->users()->lockForUpdate()->count() : 0;
            $removingSA = !in_array('super-admin', $requestedRoles, true);
            if ($removingSA && $totalSA <= 1) {
                abort(422, 'Tidak dapat menghapus role super-admin terakhir.');
            }
        }

        // --- Update email & password (jika diubah) ---
        $user->email = $validated['email'];
        if (!empty($validated['password'])) {
            $user->password = \Hash::make($validated['password']);
        }
        $user->save();

        // --- Update roles ---
        $user->syncRoles($requestedRoles);

        return redirect()
            ->route('admin.user-roles.index')
            ->with('success', "Data user {$user->name} berhasil diperbarui (termasuk email/password & role).");
    });
}


    private function authorizeTarget(User $actor, User $target): void
    {
        // Super-Admin bebas
        if ($actor->hasRole('super-admin')) return;

        // HR-Admin tidak boleh menyentuh user dengan role di luar subset yang diizinkan
        if ($actor->hasRole('hr-admin')) {
            $forbidden = $target->roles->pluck('name')
                ->filter(fn($r)=> !in_array($r, $this->hrAssignable, true));
            if ($forbidden->isNotEmpty()) {
                abort(403, 'Anda tidak boleh mengelola pengguna ini.');
            }
            return;
        }

        abort(403);
    }
}
