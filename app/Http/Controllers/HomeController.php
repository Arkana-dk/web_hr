<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

       if ($user->hasRole('super-admin')) {
        return redirect()->route('superadmin.dashboard');
    }

    if ($user->hasAnyRole(['system-admin','admin'])) {
        return redirect()->route('admin.dashboard');
    }

    if ($user->hasRole('hr-staff')) {
        return redirect()->route('hr.dashboard');
    }

    if ($user->hasRole('payroll-staff')) {
        return redirect()->route('payroll.dashboard');
    }

    if ($user->hasRole('employee') || $user->hasRole('user')) {
        return redirect()->route('employee.dashboard');
    }

       // Fallback aman: tampilkan 403 dengan tombol logout
        return response()->view('errors/403-no-dashboard', [
            'message' => 'No dashboard configured for your role.'
        ], 403);
    }
}
