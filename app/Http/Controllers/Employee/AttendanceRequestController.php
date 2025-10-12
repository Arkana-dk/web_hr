<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceRequest;
use App\Models\Notification;


class AttendanceRequestController extends Controller
{
    /**
     * Daftar riwayat pengajuan presensi.
     */
    public function index()
    {
        $employee = Auth::user()->employee;
        abort_unless($employee, 403, 'Anda tidak terdaftar sebagai pegawai.');

        $requests = $employee
            ->attendanceRequests()
            ->orderByDesc('date')
            ->paginate(10);

        return view('employee.pages.attendance-requests.history', compact('requests'));
    }

    public function history()
    {
        $employee = Auth::user()->employee;
        $requests = AttendanceRequest::where('employee_id', $employee->id)
                    ->latest()
                    ->paginate(10);

        return view('employee.pages.attendance-requests.history', compact('requests'));
    }


    /**
     * Form pengajuan presensi.
     */
    public function create()
    {
        return view('employee.pages.attendance-requests.index');
    }

    /**
     * Simpan pengajuan presensi baru.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date'   => 'required|date',
            'type' => 'required|in:check_in,check_out,check_in_out',
            'reason' => 'nullable|string|max:255',
        ]);

        $employee = Auth::user()->employee;
        abort_unless($employee, 403, 'Anda tidak terdaftar sebagai pegawai.');

        // Simpan pengajuan
        $requestRecord = $employee->attendanceRequests()->create($data);

        // Kirim notifikasi ke admin
        Notification::create([
            'employee_id' => $employee->id,
            'title'       => 'Pengajuan Koreksi Presensi',
            'message'     => $employee->name . ' mengajukan koreksi presensi pada tanggal ' . $data['date'] . ' untuk ' . strtoupper($data['type']) . '.',
            'type'        => 'attendance_request',
        ]);

        return redirect()
            ->route('employee.attendance.requests.create')
            ->with('success', 'Permohonan presensi berhasil dikirim.');
    }

}
