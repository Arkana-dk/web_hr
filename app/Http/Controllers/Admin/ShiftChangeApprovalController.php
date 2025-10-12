<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ShiftChangeRequest;
use App\Models\WorkSchedule;
use App\Models\Notification; // ⬅️ tambahkan ini

class ShiftChangeApprovalController extends Controller
{
    public function index(Request $request)
    {
        $query = ShiftChangeRequest::with(['employee', 'fromShift', 'toShift', 'reviewer']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $requests = $query->orderByDesc('date')->paginate(10);

        return view('admin.pages.shift-change-requests.index', compact('requests'));
    }

    public function approve($id)
    {
        $request = ShiftChangeRequest::with(['employee','toShift'])->findOrFail($id);

        if ($request->status !== 'pending') {
            return back()->with('error', 'Pengajuan sudah diproses.');
        }

        // Update status permintaan
        $request->update([
            'status'      => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        // Debug log
        \Log::info('UPDATE SCHEDULE', [
            'employee_id' => $request->employee_id,
            'work_date'   => $request->date,
            'shift_to'    => $request->to_shift_id
        ]);

        // Update schedule
        $updated = WorkSchedule::where('employee_id', $request->employee_id)
            ->where('work_date', $request->date)
            ->update(['shift_id' => $request->to_shift_id]);

        if ($updated == 0) {
            return back()->with('error', 'Work schedule tidak ditemukan untuk update!');
        }

        // 🔔 Notifikasi ke karyawan
        Notification::create([
            'employee_id' => $request->employee_id,
            'title'       => 'Pindah Shift Disetujui',
            'message'     => 'Pengajuan pindah shift tanggal ' . $request->date .
                             ' disetujui. Shift baru: ' . optional($request->toShift)->name,
            'type'        => 'shift_change_approved',
        ]);

        return back()->with('success', 'Pengajuan pindah shift disetujui & jadwal diperbarui.');
    }

    public function reject($id)
    {
        $request = ShiftChangeRequest::with('employee')->findOrFail($id);

        if ($request->status !== 'pending') {
            return back()->with('error', 'Pengajuan sudah diproses.');
        }

        $request->update([
            'status'      => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        // 🔔 Notifikasi ke karyawan
        Notification::create([
            'employee_id' => $request->employee_id,
            'title'       => 'Pindah Shift Ditolak',
            'message'     => 'Pengajuan pindah shift tanggal ' . $request->date . ' ditolak.',
            'type'        => 'shift_change_rejected',
        ]);

        return back()->with('success', 'Pengajuan ditolak.');
    }
    public function markAllAsRead()
    {
        \App\Models\Notification::where('is_read', false)->update(['is_read' => true]);

        return back()->with('success', 'Semua notifikasi telah ditandai sudah dibaca.');
    }

}
