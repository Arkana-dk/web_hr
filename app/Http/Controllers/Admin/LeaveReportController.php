<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\{LeaveRequest, LeaveType, Department, Employee};

class LeaveReportController extends Controller
{
    public function index(Request $r)
    {
        $year  = (int)($r->input('year', now()->year));
        $typeId = $r->input('type');
        $deptId = $r->input('department');

        $q = LeaveRequest::with(['employee.department','type'])
            ->whereYear('start_date', $year)
            ->where('status','approved');

        if ($typeId) $q->where('leave_type_id', $typeId);
        if ($deptId) $q->whereHas('employee', fn($qq)=>$qq->where('department_id', $deptId));

        $approved = $q->get();

        // Ringkasan per karyawan
        $summary = $approved->groupBy('employee_id')->map(function($rows){
            $days = $rows->sum(fn($lr)=> $lr->days ?? $lr->start_date->diffInDays($lr->end_date)+1);
            return [
                'employee' => optional($rows->first()->employee)->name,
                'department' => optional(optional($rows->first()->employee)->department)->name,
                'days' => $days,
            ];
        })->values();

        // Komposisi per jenis cuti (untuk chart pie)
        $byType = $approved->groupBy('leave_type_id')->map(function($rows){
            return $rows->sum(fn($lr)=> $lr->days ?? $lr->start_date->diffInDays($lr->end_date)+1);
        });

        // Per departemen (bar chart)
        $byDept = $approved->groupBy(fn($lr)=> optional($lr->employee?->department)->name ?: '—')->map(function($rows){
            return $rows->sum(fn($lr)=> $lr->days ?? $lr->start_date->diffInDays($lr->end_date)+1);
        });

        $leaveTypes = LeaveType::orderBy('name')->get();
        $departments = class_exists(\App\Models\Department::class) ? \App\Models\Department::orderBy('name')->get() : collect();

        return view('admin.pages.leave-reports.index', [
            'year'=>$year,
            'summary'=>$summary,
            'byTypeLabels'=> $leaveTypes->whereIn('id',$byType->keys())->pluck('name')->values(),
            'byTypeValues'=> $byType->values(),
            'byDeptLabels'=> $byDept->keys(),
            'byDeptValues'=> $byDept->values(),
            'leaveTypes'=>$leaveTypes,
            'departments'=>$departments,
        ]);
    }
}
