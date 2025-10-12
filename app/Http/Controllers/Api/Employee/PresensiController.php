<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Attendance;

class PresensiController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'date'               => 'required|date',
            'check_in_time'      => 'required|date_format:H:i:s',
            'check_in_location'  => 'nullable|string|max:255',
            'check_in_latitude'  => 'nullable|numeric|between:-90,90',
            'check_in_longitude' => 'nullable|numeric|between:-180,180',
            'status'             => 'required|in:present,late,absent,excused',
            'photo'              => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $employee = Auth::user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => false,
                'message' => 'Akun ini tidak terhubung dengan karyawan',
            ], 403);
        }

        $data = $request->only([
            'date',
            'check_in_time',
            'check_in_location',
            'check_in_latitude',
            'check_in_longitude',
            'status',
        ]);

        // Simpan foto jika ada
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('attendances', 'public');
            $data['photo_path'] = $path;
        }

        // Tambahkan employee_id untuk disimpan
        $data['employee_id'] = $employee->id;

        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date'        => $data['date'],
            ],
            $data
        );

        return response()->json([
            'status'  => true,
            'message' => 'Presensi berhasil disimpan',
            'data'    => $attendance,
        ]);
    }
}
