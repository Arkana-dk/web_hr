<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\OvertimeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OvertimeRequestController extends Controller
{
    public function index(Request $request)
    {
        $q = OvertimeRequest::with('employee')->latest();

        if ($request->filled('status')) {
            $q->where('status',$request->status);
        }
        if ($request->filled('date')) {
            $q->whereDate('date',$request->date);
        }

        return response()->json($q->paginate(10));
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'date'        => 'required|date',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'reason'      => 'required|string|max:255',
        ]);
        if ($v->fails()) {
            return response()->json(['errors'=>$v->errors()], 422);
        }

        $ot = OvertimeRequest::create([
            'employee_id' => $request->employee_id,
            'date'        => $request->date,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'reason'      => $request->reason,
            'status'      => 'pending',
        ]);

        return response()->json($ot, 201);
    }

    public function approve($id)
    {
        $ot = OvertimeRequest::findOrFail($id);
        $ot->update(['status'=>'approved']);
        return response()->json(['message'=>'Lembur disetujui.']);
    }

    public function reject($id)
    {
        $ot = OvertimeRequest::findOrFail($id);
        $ot->update(['status'=>'rejected']);
        return response()->json(['message'=>'Lembur ditolak.']);
    }

    public function show($id)
    {
        $ot = OvertimeRequest::with('employee')->findOrFail($id);
        return response()->json($ot);
    }
}
