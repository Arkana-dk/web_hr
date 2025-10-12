<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceRequest;
use App\Models\Attendance;
use App\Models\Calendar;
use Carbon\Carbon;

class AttendanceRequestController extends Controller
{
    public function index(Request $request)
    {
        $q = AttendanceRequest::with(['employee','reviewer'])
            ->orderBy('submission_time','desc')
            ->when($request->filled('status'), fn($q) => $q->where('status',$request->status))
            ->when($request->filled('date'),   fn($q) => $q->whereDate('date',$request->date));

        $data = $q->paginate($request->input('per_page',15));
        return response()->json($data);
    }

    public function approve($id)
    {
        $r = AttendanceRequest::findOrFail($id);
        $r->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->id(),
        ]);

        Attendance::create([
            'employee_id'    => $r->employee_id,
            'date'           => $r->date,
            'status'         => $r->type === 'late' ? 'late' : 'present',
            'check_in_time'  => null,
            'check_out_time' => null,
            'notes'          => 'Approved from request',
        ]);

        return response()->json(['message'=>'Approved']);
    }

    public function reject($id)
    {
        $r = AttendanceRequest::findOrFail($id);
        $r->update([
            'status'      => 'rejected',
            'reviewed_by' => auth()->id(),
        ]);
        return response()->json(['message'=>'Rejected']);
    }
}
