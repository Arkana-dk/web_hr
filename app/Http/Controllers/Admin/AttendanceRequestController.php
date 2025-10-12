<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRequest;
use App\Models\Attendance;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AttendanceRequestController extends Controller
{
        public function index(Request $request)
    {
        {
        $query = AttendanceRequest::with(['employee', 'reviewer'])->orderBy('submission_time', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        $requests = $query->paginate(15);

        return view('admin.pages.attendance-request.index', compact('requests'));
    }
    }


    public function approve($id)
    {
        $attendanceRequest = AttendanceRequest::findOrFail($id);

        $attendanceRequest->update([
            'status'      => 'approved',
            'reviewed_by' => Auth::id(),
        ]);

        $attendance = Attendance::create([
            'employee_id'    => $attendanceRequest->employee_id,
            'date'           => $attendanceRequest->date,
            'status'         => $attendanceRequest->type === 'late' ? 'late' : 'present',
            'check_in_time'  => null,
            'check_out_time' => null,
            'notes'          => 'Disetujui dari pengajuan presensi',
        ]);

        $actor = auth()->user();
        Notification::create([
            'employee_id' => $attendanceRequest->employee_id,
            'title'       => 'Pengajuan Presensi Disetujui',
            'message'     => 'Pengajuan koreksi presensi kamu pada tanggal ' . $attendanceRequest->date . ' telah disetujui.',
            'type'        => 'attendance_request_approved',
            'is_read'     => false,
            'by_user_id'  => $actor->id,
            'meta'        => [
                'by_id'   => $actor->id,
                'by_name' => $actor->name,
                'by_role' => $actor->getRoleNames()->first() ?? 'admin',
                // rujuk ke Attendance yg baru dibuat + simpan juga id request asalnya
                'ref'     => ['type' => 'attendance', 'id' => $attendance->id],
                'source_request_id' => $attendanceRequest->id,
            ],
        ]);

        return back()->with('success', 'Pengajuan disetujui.');
    }

    public function reject($id)
    {
        $attendanceRequest = AttendanceRequest::findOrFail($id);

        $attendanceRequest->update([
            'status'      => 'rejected',
            'reviewed_by' => Auth::id(),
        ]);

        $actor = auth()->user();
        Notification::create([
            'employee_id' => $attendanceRequest->employee_id,
            'title'       => 'Pengajuan Presensi Ditolak',
            'message'     => 'Pengajuan koreksi presensi kamu pada tanggal ' . $attendanceRequest->date . ' telah ditolak.',
            'type'        => 'attendance_request_rejected',
            'is_read'     => false,
            'by_user_id'  => $actor->id,
            'meta'        => [
                'by_id'   => $actor->id,
                'by_name' => $actor->name,
                'by_role' => $actor->getRoleNames()->first() ?? 'admin',
                // rujuk ke request yang ditolak
                'ref'     => ['type' => 'attendance_request', 'id' => $attendanceRequest->id],
            ],
        ]);

        return back()->with('success', 'Pengajuan ditolak.');
    }

    
}
