<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OvertimeRequest;
use App\Models\TransportRoute;
use App\Models\Notification;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OvertimeRequestController extends Controller
{
    public function history()
    {
        $employee = Auth::user()->employee;
        abort_unless($employee, 403);

        $requests = OvertimeRequest::where('employee_id', $employee->id)
            ->orderByDesc('date')
            ->paginate(10);

        return view('employee.pages.overtime-requests.history', compact('requests'));
    }

    public function create()
    {
        $transportRoutes = TransportRoute::all();
        return view('employee.pages.overtime-requests.create', compact('transportRoutes'));
    }

    public function store(Request $request)
    {
        $employee = Auth::user()->employee;
        abort_unless($employee, 403, 'Anda tidak terdaftar sebagai pegawai.');

        $request->validate([
            'date'        => 'required|date',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i',
            'reason'      => 'required|string|max:500',
            'day_type'    => 'required|in:weekday,weekend,holiday',
            'transport_route' => 'nullable|string',
        ]);

        // Konversi jam mulai & selesai
        $start = Carbon::createFromFormat('H:i', $request->start_time);
        $end   = Carbon::createFromFormat('H:i', $request->end_time);

        // Jika lembur melewati tengah malam
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        // Simpan pengajuan lembur
        $overtime = OvertimeRequest::create([
            'employee_id'     => $employee->id,
            'date'            => $request->date,
            'start_time'      => $request->start_time,
            'end_time'        => $request->end_time,
            'reason'          => $request->reason,
            'day_type'        => $request->day_type,
            'meal_option'     => $request->has('meal_option'),
            'transport'       => $request->has('transport'),
            'transport_route' => $request->transport_route,
            'status'          => 'pending',
        ]);

        // ✅ Kirim notifikasi ke admin
        Notification::create([
            'employee_id' => $employee->id,
            'title'       => 'Pengajuan Lembur Baru',
            'message'     => $employee->name . ' mengajukan lembur pada tanggal ' . $request->date .
                            ' dari pukul ' . $request->start_time . ' sampai ' . $request->end_time,
            'type'        => 'overtime_request',
        ]);

        return redirect()
            ->route('employee.overtime.requests.create')
            ->with('success', 'Pengajuan lembur berhasil dikirim!');
    }

    public function show($id)
    {
        $employee = Auth::user()->employee;
        abort_unless($employee, 403);

        $req = OvertimeRequest::where('employee_id', $employee->id)
                              ->findOrFail($id);

        return view('employee.pages.overtime-request-show', ['request' => $req]);
    }
}
