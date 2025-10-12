<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function quickStore(Request $r)
    {
        $actor = $r->user();

        if (!$actor) {
            abort(403, 'Unauthorized');
        }

        // Validasi dasar
        $data = $r->validate([
            'email'       => 'required|email|unique:users,email',
            'password'    => 'nullable|string|min:6',
            'name'        => 'nullable|string|max:255',
            'role'        => 'nullable|string|in:user,admin', // superadmin tidak dibuat via quick
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        // Enforce role policy
        if ($actor->role === 'admin') {
            // admin cuma boleh bikin user role 'user'
            $finalRole = 'user';
        } elseif ($actor->role === 'superadmin') {
            // superadmin boleh user/admin (default user)
            $finalRole = $data['role'] ?? 'user';
        } else {
            // user biasa tidak boleh create user
            abort(403, 'Anda tidak memiliki izin untuk membuat user.');
        }

        $plainPassword = $data['password'] ?: Str::random(12);

        $user = User::create([
            'name'     => $data['name'] ?: explode('@', $data['email'])[0],
            'email'    => $data['email'],
            'password' => Hash::make($plainPassword),
            'role'     => $finalRole,
        ]);

        // Link ke employee kalau ada
        if (!empty($data['employee_id'])) {
            $emp = Employee::find($data['employee_id']);
            if ($emp && !$emp->user_id) {
                $emp->update(['user_id' => $user->id]);
            }
        } else {
            // atau link otomatis by email
            $emp = Employee::where('email', $data['email'])->first();
            if ($emp && !$emp->user_id) {
                $emp->update(['user_id' => $user->id]);
            }
        }

        return back()->with('success',
            "User {$user->email} (role: {$user->role}) berhasil dibuat. ".
            ($r->boolean('show_password') ? "Password: {$plainPassword}" : "Password di-generate otomatis.")
        );
    }
}
