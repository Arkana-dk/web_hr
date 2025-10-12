<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceRequest;

class AttendanceRequestController extends Controller
{
    /**
     * GET  /api/employee/presensi/requests
     * List this user’s attendance requests.
     */
    public function index()
    {
        $employee = Auth::user()->employee;
        if (! $employee) {
            return response()->json(['error'=>'Not an employee'],403);
        }

        $requests = $employee
            ->attendanceRequests()
            ->orderByDesc('date')
            ->paginate(10);

        return response()->json($requests);
    }

    /**
     * POST /api/employee/presensi/requests
     * Submit a new attendance request.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date'   => 'required|date',
            'type'   => 'required|in:check-in,check-out',
            'reason' => 'nullable|string|max:255',
        ]);

        $employee = Auth::user()->employee;
        if (! $employee) {
            return response()->json(['error'=>'Not an employee'],403);
        }

        $req = $employee->attendanceRequests()->create($data);

        return response()->json([
            'message' => 'Request submitted',
            'request' => $req
        ], 201);
    }

    /**
     * GET /api/employee/presensi/requests/{id}
     * View one attendance request.
     */
    public function show($id)
    {
        $employee = Auth::user()->employee;
        if (! $employee) {
            return response()->json(['error'=>'Not an employee'],403);
        }

        $req = AttendanceRequest::where('employee_id',$employee->id)
                                ->findOrFail($id);

        return response()->json($req);
    }
}
