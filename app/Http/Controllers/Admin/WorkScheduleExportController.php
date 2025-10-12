<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkScheduleExportRequest;
use App\Exports\WorkScheduleTemplateExport;
use App\Models\Department;
use App\Models\Section;
use App\Models\Position;
use App\Models\Employee;
use App\Models\Shift;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;


class WorkScheduleExportController extends Controller
{
     /**
     * GET: Halaman Download Template
     * View: resources/views/admin/pages/work-schedules/export.blade.php
     */
    public function index(Request $request)
    {
        // Ambil semua department untuk dropdown
        $departments = Department::query()
            ->orderBy('name')
            ->get(['id','name']);

        // Prefill bulan (default: bulan berjalan, format Y-m)
        $defaultMonth = Carbon::now()->format('Y-m');

        return view('admin.pages.work-schedules.export', [
            'departments'  => $departments,
            'defaultMonth' => $defaultMonth,
        ]);
    }

    public function download(WorkScheduleExportRequest $request)
    {
        [$year, $month] = explode('-', $request->month); // Y-m
        $startOfMonth = Carbon::createMidnightDate((int)$year, (int)$month, 1);
        $endOfMonth   = $startOfMonth->copy()->endOfMonth();

        // Ambil meta nama org unit
        $department = Department::findOrFail($request->department_id);
        $section    = $request->section_id ? Section::find($request->section_id) : null;
        $position   = $request->position_id ? Position::find($request->position_id) : null;

        // Query employees (aktif pada bulan tsb)
        $employeesQuery = Employee::query()
            ->select(['id','employee_number','national_identity_number','name','department_id','section_id','position_id','tmt','contract_end_date'])
            ->where('department_id', $request->department_id)
            ->when($request->section_id, fn($q) => $q->where('section_id', $request->section_id))
            ->when($request->position_id, fn($q) => $q->where('position_id', $request->position_id))
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                // Aktif jika pernah aktif di rentang bulan ini
                $q->whereNull('tmt')->orWhereDate('tmt', '<=', $endOfMonth->toDateString());
            })
            ->where(function ($q) use ($startOfMonth) {
                $q->whereNull('contract_end_date')->orWhereDate('contract_end_date', '>=', $startOfMonth->toDateString());
            })
            ->orderBy('name');

        $employees = $employeesQuery->get();

        // Shift aktif (pakai kolom name sebagai "ShiftCode")
        $shifts = Shift::orderBy('name')->get(['id','name','start_time','end_time']);


        $meta = [
            'month'      => $request->month,
            'department' => $department?->name,
            'section'    => $section?->name ?? '-',
            'position'   => $position?->name ?? '-',
            'generated_at' => now(),
        ];

        $fileName = sprintf(
            'JadwalTemplate_%s%s%s_%s.xlsx',
            str_replace(' ', '', $meta['department']),
            $section ? '--'.str_replace(' ', '', $meta['section']) : '',
            $position ? '--'.str_replace(' ', '', $meta['position']) : '',
            $meta['month']
        );

        $export = new WorkScheduleTemplateExport(
            month: $meta['month'],
            meta: $meta,
            employees: $employees,
            shifts: $shifts
            
        );

        return Excel::download($export, $fileName);
    }
}
