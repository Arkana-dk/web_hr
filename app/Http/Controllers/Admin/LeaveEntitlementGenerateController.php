<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use App\Models\{LeaveType, Employee, LeaveEntitlement};
use App\Domain\LeaveRequest\EnsureEntitlement; // ✅ perbaiki namespace

class LeaveEntitlementGenerateController extends Controller
{
    public function index(Request $r)
    {
        $leaveTypes   = LeaveType::orderBy('name')->get();
        $defaultYear  = now()->year;

        return view('admin.pages.leave-entitlements.generate', compact('leaveTypes','defaultYear'));
    }

    public function store(Request $r, EnsureEntitlement $provision) // ✅ inject EnsureEntitlement
    {
        $data = $r->validate([
            'year'             => ['required','integer','min:2000','max:2100'],
            'leave_type_ids'   => ['required','array','min:1'],
            'leave_type_ids.*' => ['integer','exists:leave_types,id'],
            'mode'             => ['required', Rule::in(['all','selected'])],
            'employee_ids'     => ['nullable','array'],
            'employee_ids.*'   => ['integer','exists:employees,id'],
            'overwrite'        => ['nullable','boolean'],
        ]);

        $year  = (int) $data['year'];
        $asOf  = Carbon::create($year, 1, 1);

        // Hindari mutasi berulang pada $asOf
        $startOfYear = (clone $asOf)->startOfYear();
        $endOfYear   = (clone $asOf)->endOfYear();

        // target employees
        $employees = $data['mode'] === 'selected'
            ? Employee::whereIn('id', $data['employee_ids'] ?? [])->get()
            : Employee::query()->get();

        if ($employees->isEmpty()) {
            return back()->withInput()->withErrors(['employee_ids' => 'Tidak ada karyawan yang dipilih/terdata.']);
        }

        $leaveTypes = LeaveType::whereIn('id', $data['leave_type_ids'])->get();
        if ($leaveTypes->isEmpty()) {
            return back()->withInput()->withErrors(['leave_type_ids' => 'Jenis cuti tidak ditemukan.']);
        }

        $overwrite = (bool) ($data['overwrite'] ?? false);
        $created = 0; $skipped = 0; $updated = 0;

        foreach ($employees as $emp) {
            foreach ($leaveTypes as $lt) {
                // cek existing entitlement periode tahun tsb
                $existing = LeaveEntitlement::where('employee_id', $emp->id)
                    ->where('leave_type_id', $lt->id)
                    ->whereDate('period_start', '<=', $endOfYear)
                    ->whereDate('period_end',   '>=', $startOfYear)
                    ->first();

                if ($existing && !$overwrite) {
                    $skipped++;
                    continue;
                }

                // provision (membuat baru jika tidak ada) — pakai EnsureEntitlement
                $provision->ensure($emp, $lt->id, $asOf);

                if ($existing) { $updated++; } else { $created++; }
            }
        }

        return back()->with('success', "Generate selesai. Dibuat: {$created}, Diupdate: {$updated}, Dilewati: {$skipped}");
    }

    // AJAX: search employee by name/number
    public function employeeSearch(Request $r)
    {
        $q = trim((string)$r->get('q', ''));

        $items = Employee::query()
            ->when($q, function($qq) use ($q) {
                $qq->where(function($w) use ($q){
                    $w->where('name','like',"%{$q}%")
                      ->orWhere('employee_number','like',"%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id','name','employee_number']);

        return response()->json(
            $items->map(fn($e)=>[
                'id'   => $e->id,
                'text' => trim(($e->employee_number ? "{$e->employee_number} - " : '').$e->name)
            ])
        );
    }
}
