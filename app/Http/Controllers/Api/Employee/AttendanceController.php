<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\Calendar;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * GET /api/employee/attendance
     * Menampilkan shift, status hari libur, dan daftar kehadiran
     */
    public function index(Request $request)
    {
        $user     = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json(['error' => 'Not an employee'], 403);
        }

        $today     = Carbon::today()->toDateString();
        $isHoliday = Calendar::where('date', $today)
                             ->where('type', 'Libur')
                             ->exists();

        $group = $employee->shiftGroups()->with('shift')->first();
        $shift = $group ? $group->shift : null;

        $attendances = $employee
            ->attendances()
            ->orderByDesc('date')
            ->paginate(10);

        return response()->json([
            'isHoliday'   => $isHoliday,
            'shift'       => $shift,
            'attendances' => $attendances,
        ]);
    }

    /**
     * POST /api/employee/attendance
     * Input presensi otomatis/manual
     */
    public function store(Request $request)
    {
        $employee = Auth::user()->employee;
        $today    = Carbon::today()->toDateString();

        if (! $employee) {
            return response()->json([
                'error' => 'User tidak memiliki data karyawan.'
            ], 403);
        }

        if (Calendar::where('date', $today)->where('type','Libur')->exists()) {
            return response()->json([
                'error' => 'Tidak bisa absen di hari libur (' . $today . ')'
            ], 422);
        }

        // Cek mode manual jika ada field waktu atau status
        $isManual = $request->has('check_in_time') || $request->has('check_out_time');

        $request->validate([
            'check_in_location'   => 'nullable|string|max:255',
            'check_out_location'  => 'nullable|string|max:255',
            'check_in_latitude'   => 'nullable|numeric|between:-90,90',
            'check_in_longitude'  => 'nullable|numeric|between:-180,180',
            'check_out_latitude'  => 'nullable|numeric|between:-90,90',
            'check_out_longitude' => 'nullable|numeric|between:-180,180',
            'photo'               => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'status'              => 'nullable|string|in:present,late,absent,excused',
            'check_in_time'       => 'nullable|date_format:H:i',
            'check_out_time'      => 'nullable|date_format:H:i',
            'notes'               => 'nullable|string|max:255',
            'date'                => $isManual ? 'required|date' : 'nullable|date',
        ]);

        $date = $isManual ? $request->date : $today;

        $attendance = Attendance::firstOrNew([
            'employee_id' => $employee->id,
            'date'        => $date
        ]);

        // Foto
        if ($request->hasFile('photo')) {
            $attendance->photo_path = $request->file('photo')->store('attendances', 'public');
        }

        // Mode manual input
        if ($isManual) {
            $attendance->check_in_time       = $request->check_in_time;
            $attendance->check_out_time      = $request->check_out_time;
            $attendance->check_in_location   = $request->check_in_location;
            $attendance->check_out_location  = $request->check_out_location;
            $attendance->check_in_latitude   = $request->check_in_latitude;
            $attendance->check_in_longitude  = $request->check_in_longitude;
            $attendance->check_out_latitude  = $request->check_out_latitude;
            $attendance->check_out_longitude = $request->check_out_longitude;
            $attendance->status              = $request->status ?? 'present';
            $attendance->notes               = $request->notes;

            $attendance->save();

            return response()->json([
                'message' => 'Presensi manual berhasil disimpan.',
                'data'    => $attendance
            ]);
        }

        // Mode otomatis (check-in)
        if (!$attendance->check_in_time) {
            $attendance->check_in_time      = now()->toTimeString();
            $attendance->check_in_location  = $request->check_in_location;
            $attendance->check_in_latitude  = $request->check_in_latitude;
            $attendance->check_in_longitude = $request->check_in_longitude;
            $attendance->status             = now()->hour > 8 ? 'late' : 'present';

            $attendance->save();

            return response()->json([
                'message' => 'Check-in berhasil',
                'data'    => $attendance
            ], 201);
        }

        // Check-out
        if (!$attendance->check_out_time) {
            $attendance->check_out_time      = now()->toTimeString();
            $attendance->check_out_location  = $request->check_out_location;
            $attendance->check_out_latitude  = $request->check_out_latitude;
            $attendance->check_out_longitude = $request->check_out_longitude;

            $attendance->save();

            return response()->json([
                'message' => 'Check-out berhasil',
                'data'    => $attendance
            ], 200);
        }

        return response()->json(['message' => 'Sudah check-in dan check-out hari ini.'], 409);
    }

    /**
     * GET /api/employee/attendance/history
     * Menampilkan semua riwayat presensi user
     */
    public function history(Request $request)
    {
        $user     = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak memiliki data karyawan.'
            ], 403);
        }

        $attendances = Attendance::where('employee_id', $employee->id)
            ->orderByDesc('date')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $attendances
        ]);
    }
}
// End of AttendanceController.php
// --- IGNORE ---