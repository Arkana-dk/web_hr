<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\OvertimeRequest;

class OvertimeRequestController extends Controller
{
    /**
     * GET /api/employee/overtime-request
     * Menampilkan daftar pengajuan lembur milik pegawai yang sedang login.
     */
    public function index(Request $request)
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak terdaftar sebagai pegawai.'
            ], 403);
        }

        $overtimes = $employee->overtimeRequests()
                              ->latest()
                              ->paginate(10);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar pengajuan lembur berhasil diambil.',
            'data' => $overtimes
        ]);
    }

    /**
     * POST /api/employee/overtime-request
     * Menyimpan pengajuan lembur baru oleh pegawai.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date'       => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
            'reason'     => 'nullable|string|max:255',
        ]);

        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak terdaftar sebagai pegawai.'
            ], 403);
        }

        $data['status'] = 'pending';

        $overtime = $employee->overtimeRequests()->create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan lembur berhasil disimpan.',
            'data' => $overtime
        ], 201);
    }

    /**
     * GET /api/employee/overtime-request/{id}
     * Menampilkan detail lembur tertentu milik pegawai.
     */
    public function show($id, Request $request)
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak terdaftar sebagai pegawai.'
            ], 403);
        }

        $overtime = $employee->overtimeRequests()->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Detail pengajuan lembur berhasil diambil.',
            'data' => $overtime
        ]);
    }

    /**
     * GET /api/employee/overtime-request/history
     * Menampilkan seluruh riwayat lembur (tanpa pagination).
     */
    public function history(Request $request)
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak terdaftar sebagai pegawai.'
            ], 403);
        }

        $requests = $employee->overtimeRequests()
                             ->orderByDesc('date')
                             ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Riwayat lembur berhasil diambil.',
            'data' => $requests
        ]);
    }
}
// End of OvertimeRequestController.php