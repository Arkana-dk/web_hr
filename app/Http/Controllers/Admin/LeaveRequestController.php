<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\LeaveRequest;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\LeaveType;
use App\Models\Department; // kalau ada tabel department


use App\Domain\LeaveRequest\Contracts\LeaveRequestServiceContract;
use Illuminate\Support\Carbon;

class LeaveRequestController extends Controller
{
    public function index(Request $r)
    {
        $q = LeaveRequest::with(['employee.department', 'type', 'reviewer'])
            ->latest('created_at');

        // 🔍 Filter status
        if ($r->filled('status')) {
            $q->where('status', $r->status);
        }

        // 🔍 Filter tanggal
        if ($r->filled('start')) {
            $q->whereDate('start_date', '>=', Carbon::parse($r->start));
        }
        if ($r->filled('end')) {
            $q->whereDate('end_date', '<=', Carbon::parse($r->end));
        }

        // 🔍 Filter leave type
        if ($r->filled('type')) {
            $q->where('leave_type_id', $r->type);
        }

        // 🔍 Filter employee
        if ($r->filled('employee')) {
            $q->where('employee_id', $r->employee);
        }

        // 🔍 Filter department (via relasi employee)
        if ($r->filled('department')) {
            $q->whereHas('employee', fn($qq) =>
                $qq->where('department_id', $r->department)
            );
        }

        // 🔍 Search bebas (nama pegawai / alasan cuti)
        if ($r->filled('q')) {
            $term = '%'.$r->q.'%';
            $q->where(function($qq) use ($term) {
                $qq->whereHas('employee', fn($ee) =>
                        $ee->where('name', 'like', $term)
                           ->orWhere('employee_number', 'like', $term)
                )
                ->orWhere('reason','like',$term);
            });
        }

        $leaveRequests = $q->paginate(12)->withQueryString();

        // Data tambahan untuk filter dropdown
        $leaveTypes   = LeaveType::orderBy('name')->get();
        $employees    = Employee::orderBy('name')->get();
        $departments  = \App\Models\Department::orderBy('name')->get(); // kalau ada tabel departments

        // Statistik ringkas
        $stats = [
            'pending' => LeaveRequest::where('status','pending')->count(),
            'approved_this_month' => LeaveRequest::where('status','approved')
                ->whereMonth('start_date', Carbon::now()->month)
                ->whereYear('start_date', Carbon::now()->year)
                ->count(),
            'avg_remaining' => '—', // bisa diisi lewat service LeaveBalance
        ];

        return view('admin.pages.leave-request.index', compact(
            'leaveRequests','leaveTypes','employees','departments','stats'
        ));
    }

        public function show($id)
    {
        // Ambil leave request beserta relasi yang dibutuhkan
        $leave = LeaveRequest::with([
            'employee.department',
            'employee.position',
            'type',
            'approvals.approver',
            'reviewer'
        ])->findOrFail($id);

        // Hitung saldo cuti (pakai helper kompatibilitas yang sudah ada di model LeaveRequest)
        $balances = [
            'used' => LeaveRequest::usedLeaveDaysThisYear($leave->employee->user_id, $leave->type->name),
            'remaining' => LeaveRequest::remainingLeaveQuota($leave->employee->user_id, $leave->type->name),
        ];

        return view('admin.pages.leave-request.show', compact('leave','balances'));
    }


    /**
     * Admin membuat pengajuan cuti (opsional) atau karyawan (ESS) kalau route-nya memang diarahkan ke sini.
     * Kirimkan employee_id bila admin membuatkan atas nama karyawan tertentu.
     */
    public function store(Request $r, LeaveRequestServiceContract $svc)
    {
        $r->validate([
            'employee_id'   => ['nullable', 'exists:employees,id'],
            'leave_type_id' => ['required','exists:leave_types,id'],
            'start_date'    => ['required','date'],
            'end_date'      => ['required','date','after_or_equal:start_date'],
            'reason'        => ['nullable','string'],
            'attachment'    => ['nullable','file','max:2048'],
        ]);

        try {
            // Tentukan employee:
            if ($r->filled('employee_id')) {
                $emp = Employee::findOrFail((int)$r->employee_id);
            } else {
                // fallback ESS: cari employee by user login
                $emp = Employee::where('user_id', Auth::id())->firstOrFail();
            }

            $path = $r->file('attachment')?->store('attachments/leave', 'public');

            $lr = $svc->create(
                $emp,
                (int)$r->leave_type_id,
                Carbon::parse($r->start_date),
                Carbon::parse($r->end_date),
                $r->reason,
                $path
            );

            return back()->with('success', 'Pengajuan cuti berhasil dibuat.');
            // atau: return redirect()->route('admin.leave-request.show', $lr->id);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['leave' => $e->getMessage()]);
        }
    }

    public function approve($id, LeaveRequestServiceContract $svc)
    {
        try {
            /** @var LeaveRequest $leave */
            $leave = LeaveRequest::findOrFail($id);

            // Setujui via service (otomatis tulis ledger & set status/approved_by/approved_at)
            $svc->approve($leave, Auth::id());

            // Kirim notifikasi (di luar transaksi service agar tidak nested terlalu dalam)
            $actor = auth()->user();
            $start = $leave->start_date instanceof Carbon ? $leave->start_date : Carbon::parse($leave->start_date);
            $end   = $leave->end_date   instanceof Carbon ? $leave->end_date   : Carbon::parse($leave->end_date);
            $range = $start->equalTo($end) ? $start->toDateString() : $start->toDateString().' s.d. '.$end->toDateString();

            Notification::create([
                'employee_id' => $leave->employee_id,
                'title'       => 'Pengajuan Cuti Disetujui',
                'message'     => 'Pengajuan cuti kamu pada '.$range.' telah disetujui.',
                'type'        => 'leave_request_approved',
                'is_read'     => false,
                'by_user_id'  => $actor->id,
                'meta'        => [
                    'by_id'   => $actor->id,
                    'by_name' => $actor->name,
                    'by_role' => method_exists($actor, 'getRoleNames') ? ($actor->getRoleNames()->first() ?? 'admin') : 'admin',
                    'ref'     => ['type' => 'leave_request', 'id' => $leave->id],
                    'start_date' => $start->toDateString(),
                    'end_date'   => $end->toDateString(),
                ],
            ]);

            return back()->with('success', 'Cuti berhasil disetujui.');
        } catch (\Throwable $e) {
            return back()->withErrors(['leave' => $e->getMessage()]);
        }
    }

    public function reject($id, LeaveRequestServiceContract $svc)
    {
        try {
            /** @var LeaveRequest $leave */
            $leave = LeaveRequest::findOrFail($id);

            // Tolak via service (status → rejected)
            $svc->reject($leave);

            $actor = auth()->user();
            $start = $leave->start_date instanceof Carbon ? $leave->start_date : Carbon::parse($leave->start_date);
            $end   = $leave->end_date   instanceof Carbon ? $leave->end_date   : Carbon::parse($leave->end_date);
            $range = $start->equalTo($end) ? $start->toDateString() : $start->toDateString().' s.d. '.$end->toDateString();

            Notification::create([
                'employee_id' => $leave->employee_id,
                'title'       => 'Pengajuan Cuti Ditolak',
                'message'     => 'Pengajuan cuti kamu pada '.$range.' telah ditolak.',
                'type'        => 'leave_request_rejected',
                'is_read'     => false,
                'by_user_id'  => $actor->id,
                'meta'        => [
                    'by_id'   => $actor->id,
                    'by_name' => $actor->name,
                    'by_role' => method_exists($actor, 'getRoleNames') ? ($actor->getRoleNames()->first() ?? 'admin') : 'admin',
                    'ref'     => ['type' => 'leave_request', 'id' => $leave->id],
                    'start_date' => $start->toDateString(),
                    'end_date'   => $end->toDateString(),
                ],
            ]);

            return back()->with('success', 'Cuti berhasil ditolak.');
        } catch (\Throwable $e) {
            return back()->withErrors(['leave' => $e->getMessage()]);
        }
    }
}
