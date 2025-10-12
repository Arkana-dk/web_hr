<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Tampilkan form login
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Proses login
    public function login(Request $request)
    {
      $credentials =  $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        
        if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        $user = auth()->user();

        switch ($user->role) {
            case 'superadmin':
                return redirect()->route('superadmin.dashboard');
            case 'admin':
                return redirect()->route('admin.dashboard');
            case 'hr-staff':
                return redirect()->route('hr.dashboard');
            case 'payroll-staff':
                return redirect()->route('payroll.dashboard');
            case 'employee':
            case 'user':
            default:
                return redirect()->route('employee.dashboard');
        }
    }

            return redirect()->back()->withErrors(['loginError' => 'Email atau password salah']);
            
        }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
    
}
