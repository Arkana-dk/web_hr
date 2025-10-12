<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Notification;
use App\Models\Calendar;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $attendances = Attendance::with('employee')
            ->when($request->date,   fn($q) => $q->whereDate('date', $request->date))
            ->when($request->status, fn($q) => $q->where('status',    $request->status))
            ->latest('date')
            ->paginate(15);

        return view('admin.pages.attendance.index', compact('attendances'));
    }

    public function show(Attendance $attendance)
    {
        return view('admin.pages.attendance.show', compact('attendance'));
    }

    public function edit(Attendance $attendance)
    {
        // Jika hari absensi itu bertipe 'Libur', tolak edit
        if (Calendar::where('date', $attendance->date)
                    ->where('type', 'Libur')
                    ->exists()
        ) {
            return redirect()
                ->route('admin.attendance.index')
                ->with('error', 'Tidak bisa mengubah: ' . Carbon::parse($attendance->date)->isoFormat('D MMMM Y') . ' adalah hari libur.');
        }

        return view('admin.pages.attendance.edit', compact('attendance'));
    }

public function update(Request $request, Attendance $attendance)
{
    // ✅ Validasi terlebih dahulu
    if (Calendar::where('date', $attendance->date)
                ->where('type', 'Libur')
                ->exists()
    ) {
        return back()
            ->with('error', 'Tidak bisa mengubah data pada hari libur.');
    }

    $data = $request->validate([
        'check_in_time'     => 'nullable|date_format:H:i',
        'check_out_time'    => 'nullable|date_format:H:i|after:check_in_time',
        'status'            => 'required|in:present,late,absent,excused',
        'notes'             => 'nullable|string',
        'check_in_location' => 'nullable|string|max:255',
    ]);

    // ✅ Lakukan update setelah validasi
    $attendance->update($data);

    // ✅ Tambahkan notifikasi setelah update berhasil
    $employee = $attendance->employee;
    $status = $data['status'];

    $title = null;
    $message = null;
    $type = null;

    switch ($status) {
        case 'late':
            $title = 'Status Kehadiran: Terlambat';
            $message = $employee->name . ' ditandai sebagai terlambat pada ' . Carbon::parse($attendance->date)->format('d-m-Y');
            $type = 'late';
            break;

        case 'absent':
            $title = 'Status Kehadiran: Alpa';
            $message = $employee->name . ' ditandai tidak hadir pada ' . Carbon::parse($attendance->date)->format('d-m-Y');
            $type = 'no_check_in';
            break;

        case 'excused':
            $title = 'Status Kehadiran: Izin';
            $message = $employee->name . ' telah diberikan izin pada ' . Carbon::parse($attendance->date)->format('d-m-Y');
            $type = 'attendance_request_approved';
            break;

        case 'present':
            $title = 'Status Kehadiran Diperbarui';
            $message = $employee->name . ' ditandai hadir pada ' . Carbon::parse($attendance->date)->format('d-m-Y');
            $type = 'attendance_request_approved';
            break;
    }

    if ($title && $type) {
       $actor = auth()->user();
        Notification::create([
        'employee_id' => $receiver->employee_id,   // pemilik notifikasi
        'title'       => 'Pengajuan Disetujui',
        'message'     => 'Pengajuan kamu telah disetujui.',
        'type'        => 'attendance_request_approved', // atau *_rejected / overtime / leave
        'is_read'     => false,
        'by_user_id'  => $actor->id,                // opsional, tapi enak buat index/join
        'meta'        => [
            'by_id'   => $actor->id,
            'by_name' => $actor->name,
            'by_role' => $actor->getRoleNames()->first() ?? 'admin',
            'ref'     => ['type' => 'attendance', 'id' => $requestModel->id], // opsional
        ],
        ]);

    }

    return redirect()
        ->route('admin.attendance.index')
        ->with('success', 'Absensi berhasil diperbarui.');
}

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return redirect()
            ->route('admin.attendance.index')
            ->with('success', 'Data absensi berhasil dihapus.');
    }
}
