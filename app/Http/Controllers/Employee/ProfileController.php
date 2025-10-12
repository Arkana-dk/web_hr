<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman profil karyawan yang sedang login.
     */
    public function show()
    {
        // Dari User, ambil data employee lewat relasi
        $user = Auth::user();
        $employee = $user->employee;

        return view('employee.pages.profile', compact('user', 'employee'));
    }
}
