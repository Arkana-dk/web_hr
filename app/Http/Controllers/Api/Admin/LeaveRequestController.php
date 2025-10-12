<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $q = LeaveRequest::with('employee','reviewer')->latest();

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('date')) {
            $q->whereDate('start_date', $request->date);
        }

        return response()->json($q->paginate(10));
    }

    public function approve($id)
    {
        $leave = LeaveRequest::findOrFail($id);
        $leave->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
        ]);
        return response()->json(['message'=>'Cuti disetujui.']);
    }

    public function reject($id)
    {
        $leave = LeaveRequest::findOrFail($id);
        $leave->update([
            'status'      => 'rejected',
            'approved_by' => Auth::id(),
        ]);
        return response()->json(['message'=>'Cuti ditolak.']);
    }
}
