<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShiftChangeRequest;
use App\Models\WorkSchedule;
use App\Models\Shift;
use App\Models\Notification; // ⬅️ tambahkan
use Illuminate\Support\Facades\Auth;

class ShiftChangeController extends Controller
{   
    public function history()
    {
        $employee = Auth::user()->employee;

        $requests = $employee->shiftChangeRequests()
            ->with(['fromShift', 'toShift'])
            ->orderByDesc('date')
            ->paginate(10);

        return view('employee.pages.shift-change-requests.history', compact('requests'));
    }

    public function create()
    {
        $employee = Auth::user()->employee;
        $shifts = Shift::all();

        return view('employee.pages.shift-change-requests.create', compact('shifts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date'        => 'required|date',
            'to_shift_id' => 'required|exists:shifts,id',
            'reason'      => 'nullable|string|max:255',
        ]);

        $employee = Auth::user()->employee;

        // Ambil jadwal kerja pada tanggal tersebut
        $schedule = WorkSchedule::where('employee_id', $employee->id)
            ->where('work_date', $request->date)
            ->first();

        if (!$schedule) {
            return back()->withErrors(['date' => 'Jadwal tidak ditemukan untuk tanggal tersebut.']);
        }

        if ($schedule->shift_id == $request->to_shift_id) {
            return back()->withErrors(['to_shift_id' => 'Shift tujuan sama dengan shift sekarang.']);
        }

        $req = ShiftChangeRequest::create([
            'employee_id'   => $employee->id,
            'date'          => $request->date,
            'from_shift_id' => $schedule->shift_id,
            'to_shift_id'   => $request->to_shift_id,
            'reason'        => $request->reason,
            'status'        => 'pending',
        ]);

        // 🔔 Notifikasi ke admin/HR: pengajuan pindah shift dibuat
        Notification::create([
            'employee_id' => $employee->id,
            'title'       => 'Pengajuan Pindah Shift',
            'message'     => $employee->name . ' mengajukan pindah shift pada ' . $request->date . '.',
            'type'        => 'shift_change_request',
        ]);

        return redirect()
            ->route('employee.shift-change-requests.create')
            ->with('success', 'Pengajuan pindah shift berhasil dikirim.');
    }
}
