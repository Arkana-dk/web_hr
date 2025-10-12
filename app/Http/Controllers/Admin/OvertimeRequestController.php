<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OvertimeRequest;
use App\Models\TransportRoute;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Http\Request as HttpRequest;
use App\Exports\OvertimeRequestExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;

class OvertimeRequestController extends Controller
{
    /**
     * Tampilkan daftar pengajuan lembur dengan pagination.
     */
    public function index(Request $request)
    {
        $query = OvertimeRequest::with('employee')->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('date', [$request->from_date, $request->to_date]);
        } elseif ($request->filled('from_date')) {
            $query->where('date', '>=', $request->from_date);
        } elseif ($request->filled('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }

        $overtimeRequests = $query->paginate(10)->appends($request->query());
        $pendingCount = OvertimeRequest::where('status', 'pending')->count();

        // 🔥 Filter statistik transport mengikuti filter tanggal
        $transportBaseQuery = OvertimeRequest::query();
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $transportBaseQuery->whereBetween('date', [$request->from_date, $request->to_date]);
        } elseif ($request->filled('from_date')) {
            $transportBaseQuery->where('date', '>=', $request->from_date);
        } elseif ($request->filled('to_date')) {
            $transportBaseQuery->where('date', '<=', $request->to_date);
        }

        $transportRouteStats = $transportBaseQuery
            ->select('transport_route', DB::raw('COUNT(DISTINCT employee_id) as total'))
            ->groupBy('transport_route')
            ->orderByDesc('total')
            ->get();

        return view('admin.pages.overtime-requests.index', [
            'overtimeRequests' => $overtimeRequests,
            'pendingOvertimeRequests' => $pendingCount,
            'transportRouteStats' => $transportRouteStats,
        ]);
    }

    /**
     * Form tambah pengajuan lembur.
     */
    public function create()
    {
        $employees = Employee::with(['department', 'position'])->orderBy('name')->get();
        $transportRoutes = TransportRoute::orderBy('route_name')->get();

        return view('admin.pages.overtime-requests.create', compact('employees', 'transportRoutes'));
    }

    /**
     * Simpan pengajuan lembur baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'date'        => 'required|date',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'reason'      => 'required|string|max:255',
            'transport_route' => 'required|string|max:255',
            'day_type'        => 'required|string',
            'meal_option'     => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Validasi durasi minimal 30 menit
        $start = Carbon::createFromFormat('H:i', $request->start_time);
        $end = Carbon::createFromFormat('H:i', $request->end_time);
        $diffInMinutes = $start->diffInMinutes($end, false);

        if ($diffInMinutes < 30) {
            return redirect()
                ->back()
                ->withErrors(['end_time' => 'Durasi lembur minimal 30 menit.'])
                ->withInput();
        }

        // Simpan ke database
        OvertimeRequest::create([
            'employee_id'     => $request->employee_id,
            'date'            => $request->date,
            'start_time'      => $request->start_time,
            'end_time'        => $request->end_time,
            'day_type'        => $request->day_type ?? 'weekday',
            'reason'          => $request->reason,
            'transport_route' => $request->transport_route,
            'meal_option'     => $request->meal_option,
            'status'          => 'pending',
        ]);

        return redirect()
            ->route('admin.overtime-requests.index')
            ->with('success', 'Pengajuan lembur berhasil dikirim.');
    }

    // === Approve & Reject ===
    public function approve(OvertimeRequest $overtime_request)
    {
        return $this->decideOvertime($overtime_request, 'approved', 'Pengajuan Lembur Disetujui', 'overtime_request_approved');
    }

    public function reject(OvertimeRequest $overtime_request)
    {
        return $this->decideOvertime($overtime_request, 'rejected', 'Pengajuan Lembur Ditolak', 'overtime_request_rejected');
    }

    protected function decideOvertime(OvertimeRequest $overtime, string $status, string $title, string $type)
    {
        $actor = auth()->user();

        // Tentukan employee_id penerima notifikasi
        $employeeId =
            $overtime->employee_id
            ?? optional($overtime->employee)->id
            ?? optional($overtime->user)->employee_id
            ?? optional(optional($overtime->user)->employee)->id;

        if (!$employeeId) {
            return back()->with('error', 'Tidak dapat menentukan employee penerima notifikasi (employee_id null).');
        }

        $dateText = $overtime->date
            ? Carbon::parse($overtime->date)->translatedFormat('d M Y')
            : '-';

        DB::transaction(function () use ($overtime, $status, $actor, $employeeId, $title, $type, $dateText) {
            $fresh = OvertimeRequest::whereKey($overtime->getKey())->lockForUpdate()->first();

            if ($fresh->status === $status) {
                return;
            }

            // ✅ Update status + approved_by
           $fresh->forceFill([
    'status' => $status,
    'approved_by' => $actor->id, // simpan ID, bukan nama
])->save();


            // Kirim notifikasi ke karyawan
            Notification::create([
                'employee_id' => $employeeId,
                'title'       => $title,
                'message'     => "Pengajuan lembur pada tanggal {$dateText} telah " .
                                 ($status === 'approved' ? 'disetujui' : 'ditolak') . '.',
                'type'        => $type,
                'is_read'     => false,
                'by_user_id'  => $actor->id,
                'meta'        => [
                    'by_id'   => $actor->id,
                    'by_name' => $actor->name,
                    'by_role' => $actor->getRoleNames()->first() ?? 'admin',
                    'ref'     => ['type' => 'overtime', 'id' => $fresh->id],
                ],
            ]);
        });

        return redirect()
            ->route('admin.overtime-requests.index')
            ->with('success', "Pengajuan lembur {$status}.");
    }

    public function exportExcel(HttpRequest $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        return Excel::download(new OvertimeRequestExport($startDate, $endDate), 'lembur.xlsx');
    }

    public function approver()
{
    return $this->belongsTo(User::class, 'approved_by');
}

}
