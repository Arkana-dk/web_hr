<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use Carbon\Carbon;

class AttendanceSummaryController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->format('m'));
        $year  = $request->input('year',  now()->format('Y'));

        $employees = Employee::with([
            'attendances'   => fn($q) => $q->whereMonth('date',$month)->whereYear('date',$year),
            'leaveRequests' => fn($q) => $q->whereMonth('start_date',$month)->whereYear('start_date',$year),
        ])->get();

        $summary = $employees->map(fn($emp) => [
            'employee_id'   => $emp->id,
            'employee_name' => $emp->name,
            'present'       => $emp->attendances->where('status','present')->count(),
            'late'          => $emp->attendances->where('status','late')->count(),
            'absent'        => $emp->attendances->where('status','absent')->count(),
            'cuti'          => $emp->leaveRequests->where('status','approved')->count(),
            'overtime_hours'=> OvertimeRequest::where('employee_id',$emp->id)
                                ->where('status','approved')
                                ->whereMonth('date',$month)
                                ->whereYear('date',$year)
                                ->get()
                                ->sum(fn($ot) => Carbon::parse($ot->end_time)
                                                   ->diffInHours(Carbon::parse($ot->start_time))),
        ]);

        return response()->json([
            'month'   => $month,
            'year'    => $year,
            'summary' => $summary,
        ]);
    }
}
