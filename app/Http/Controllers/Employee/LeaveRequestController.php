<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

use App\Models\{LeaveRequest, LeaveType, Employee};
use App\Domain\LeaveRequest\Contracts\LeaveRequestServiceContract;
use App\Domain\LeaveRequest\Contracts\LeaveBalanceServiceContract;

class LeaveRequestController extends Controller
{
    public function history(Request $request)
    {
        $employee = Auth::user()->employee;
        abort_unless($employee, 403, 'Anda tidak terdaftar sebagai pegawai.');

        $leaveRequests = LeaveRequest::with(['type'])
            ->where('employee_id', $employee->id)
            ->orderByDesc('start_date')
            ->paginate(10);

        return view('employee.pages.leave-requests.history', compact('leaveRequests'));
    }

    public function create(LeaveBalanceServiceContract $balance)
    {
        $employee = Auth::user()->employee;
        abort_unless($employee, 403, 'Anda tidak terdaftar sebagai pegawai.');

        // Ambil semua jenis cuti
        $leaveTypes = LeaveType::orderBy('name')->get();

        // Siapkan ringkasan kuota per tipe (used & remaining) via service
        $balances = [];
        foreach ($leaveTypes as $lt) {
            try {
                // Asumsi API layanan; sesuaikan dengan servis kamu kalau berbeda nama metodenya.
                $used = method_exists($balance, 'usedThisYear')
                    ? $balance->usedThisYear($employee, $lt->id)
                    : (method_exists($balance, 'getUsed') ? $balance->getUsed($employee, $lt->id) : 0.0);

                $remaining = method_exists($balance, 'remaining')
                    ? $balance->remaining($employee, $lt->id, now())
                    : (method_exists($balance, 'getRemaining') ? $balance->getRemaining($employee, $lt->id, now()) : 0.0);

                $balances[$lt->id] = [
                    'used' => (float) $used,
                    'remaining' => (float) $remaining,
                ];
            } catch (\Throwable $e) {
                $balances[$lt->id] = ['used' => 0.0, 'remaining' => 0.0];
            }
        }

        return view('employee.pages.leave-requests.create', compact('leaveTypes', 'balances'));
    }

    public function store(
        Request $request,
        LeaveRequestServiceContract $svc,
        LeaveBalanceServiceContract $balance
    ) {
        $user = Auth::user();
        $employee = $user->employee;
        abort_unless($employee, 403, 'Anda tidak terdaftar sebagai pegawai.');

        // Validasi dasar
        $request->validate([
            'leave_type_id' => ['required','exists:leave_types,id'],
            'start_date'    => ['required','date'],
            'end_date'      => ['required','date','after_or_equal:start_date'],
            'reason'        => ['nullable','string','max:255'],
            'attachment'    => ['nullable','file','mimes:pdf,jpg,jpeg,png','max:2048'],
        ]);

        $leaveType = LeaveType::findOrFail((int)$request->leave_type_id);

        // Jika tipe cuti mewajibkan lampiran → wajib file
        if ($leaveType->requires_attachment) {
            $request->validate([
                'attachment' => ['required','file','mimes:pdf,jpg,jpeg,png','max:2048'],
            ]);
        }

        $start = Carbon::parse($request->start_date);
        $end   = Carbon::parse($request->end_date);

        // Hitung sisa kuota pakai service (konsisten dengan admin)
        try {
            $remaining = method_exists($balance, 'remaining')
                ? $balance->remaining($employee, $leaveType->id, now())
                : (method_exists($balance, 'getRemaining') ? $balance->getRemaining($employee, $leaveType->id, now()) : 0.0);
        } catch (\Throwable $e) {
            $remaining = 0.0;
        }

        // Upload attachment (opsional)
        $path = $request->file('attachment')?->store('attachments/leave', 'public');

        // Buat pengajuan via service agar seluruh aturan terpusat
        try {
            $leave = $svc->create(
                $employee,
                $leaveType->id,
                $start,
                $end,
                $request->reason,
                $path
            );

            // Opsional: jika ingin validasi over-quota sebelum create(), kamu bisa minta WorkingDaysService
            // untuk menghitung hari kerja yang akan dipakai lalu bandingkan dengan $remaining.
            // Namun sebaiknya biarkan service melakukan seluruh validasi bisnis.

            return redirect()
                ->route('employee.leave.history')
                ->with('success', 'Pengajuan cuti berhasil dikirim.');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['leave' => $e->getMessage()]);
        }
    }

    // (opsional) detail 1 pengajuan
    public function show($id)
    {
        $employee = Auth::user()->employee;
        abort_unless($employee, 403, 'Anda tidak terdaftar sebagai pegawai.');

        $leave = LeaveRequest::with('type')
            ->where('employee_id', $employee->id)
            ->findOrFail($id);

        return view('employee.pages.leave-requests.show', compact('leave'));
    }
}
