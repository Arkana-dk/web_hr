<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;

class LeaveRequestController extends Controller
{
    /**
     * GET /api/employee/cuti
     * Menampilkan daftar cuti user login, dengan opsi filter status & pagination
     */
    public function index(Request $request)
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda bukan pegawai.'
            ], 403);
        }

        $leaves = $employee->leaveRequests()
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderByDesc('start_date')
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar pengajuan cuti berhasil diambil.',
            'data' => $leaves
        ]);
    }

    /**
     * POST /api/employee/cuti
     * Mengajukan cuti baru
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'type'       => 'required|string|max:50',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|min:3',
        ]);

        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda bukan pegawai.'
            ], 403);
        }

        $data['status'] = 'pending';

        $leave = $employee->leaveRequests()->create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan cuti berhasil dikirim.',
            'data' => $leave
        ], 201);
    }

    /**
     * GET /api/employee/cuti/{id}
     * Melihat detail cuti milik user login
     */
    public function show($id, Request $request)
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda bukan pegawai.'
            ], 403);
        }

        $leave = $employee->leaveRequests()->find($id);

        if (! $leave) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data cuti tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail cuti berhasil diambil.',
            'data' => $leave
        ]);
    }

    /**
     * GET /api/employee/cuti/history
     * Menampilkan semua riwayat cuti user login tanpa pagination
     */
    public function history(Request $request)
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda bukan pegawai.'
            ], 403);
        }

        $leaves = $employee->leaveRequests()
            ->orderByDesc('start_date')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Riwayat cuti berhasil diambil.',
            'data' => $leaves
        ]);
    }
}

// End of LeaveRequestController.php
// These are recently edited files. Do not suggest code that has been deleted.  