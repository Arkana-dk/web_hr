<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{LeavePolicy, LeaveType};
use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Position;


class LeavePolicyController extends Controller
{
    public function index()
    {
        $policies = LeavePolicy::with('leaveType')
            ->orderByDesc('effective_start') // lebih relevan utk policy
            ->paginate(12);

        return view('admin.pages.leave-policies.index', compact('policies'));
    }

    public function create()
    {
        $leaveTypes  = LeaveType::orderBy('name')->get();
        $departments = class_exists(Department::class) ? Department::orderBy('name')->get() : collect();
        $positions   = class_exists(Position::class)   ? Position::orderBy('name')->get()   : collect();

        return view('admin.pages.leave-policies.create', compact('leaveTypes','departments','positions'));
    }

    public function edit(LeavePolicy $leave_policy)
    {
        $leaveTypes  = LeaveType::orderBy('name')->get();
        $departments = class_exists(Department::class) ? Department::orderBy('name')->get() : collect();
        $positions   = class_exists(Position::class)   ? Position::orderBy('name')->get()   : collect();

        return view('admin.pages.leave-policies.edit', [
            'policy'      => $leave_policy,
            'leaveTypes'  => $leaveTypes,
            'departments' => $departments,
            'positions'   => $positions,
        ]);
    }

    public function show(LeavePolicy $leave_policy)
    {
        $leave_policy->load('leaveType');
        return view('admin.pages.leave-policies.show', ['policy' => $leave_policy]);
    }


    public function store(Request $r)
    {
        $data = $r->validate([
            'name'             => 'required|string|max:120|unique:leave_policies,name',
            'leave_type_id'    => 'required|exists:leave_types,id',
            'effective_start'  => 'required|date',                     // WAJIB: kolom NOT NULL
            'effective_end'    => 'nullable|date|after_or_equal:effective_start',

            // ini bukan kolom tabel -> simpan ke JSON rules
            'annual_quota'     => 'required|numeric|min:0',
            'is_prorated'      => 'nullable|boolean',
            'allow_carry_over' => 'nullable|boolean',
            'applies_to'       => 'nullable|string|max:120',
            'applies_value'    => 'nullable|string|max:120',
        ]);

        $this->ensureNoOverlap($data);   

        if (($data['applies_to'] ?? 'all') === 'all') {
            $data['applies_value'] = null;
        }


        $payload = [
            'name'            => $data['name'],
            'leave_type_id'   => (int) $data['leave_type_id'],
            'effective_start' => $data['effective_start'],
            'effective_end'   => $data['effective_end'] ?? null,
            'rules' => [
                'annual_quota'      => (float) $data['annual_quota'],
                'is_prorated'       => (bool)  ($data['is_prorated'] ?? false),
                'allow_carry_over'  => (bool)  ($data['allow_carry_over'] ?? false),
                'applies_to'        => $data['applies_to'] ?? 'all',
                'applies_value'     => $data['applies_value'] ?? null,
                ],

        ];

        LeavePolicy::create($payload);

        return redirect()->route('admin.leave-policies.index')->with('success', 'Policy dibuat.');
    }


    public function update(Request $r, LeavePolicy $leave_policy)
    {
        $data = $r->validate([
            'name'             => 'required|string|max:120|unique:leave_policies,name,'.$leave_policy->id,
            'leave_type_id'    => 'required|exists:leave_types,id',
            'effective_start'  => 'required|date',
            'effective_end'    => 'nullable|date|after_or_equal:effective_start',

            // field ke JSON rules
            'annual_quota'     => 'required|numeric|min:0',
            'is_prorated'      => 'nullable|boolean',
            'allow_carry_over' => 'nullable|boolean',
            'applies_to'       => 'nullable|string|max:120',
            'applies_value'    => 'nullable|string|max:120',
        ]);

        $this->ensureNoOverlap($data, $leave_policy);

        $leave_policy->update([
            'name'            => $data['name'],
            'leave_type_id'   => (int) $data['leave_type_id'],
            'effective_start' => $data['effective_start'],
            'effective_end'   => $data['effective_end'] ?? null,
            'rules'           => [
                'annual_quota'      => (float) $data['annual_quota'],
                'is_prorated'       => (bool)  ($data['is_prorated'] ?? false),
                'allow_carry_over'  => (bool)  ($data['allow_carry_over'] ?? false),
                'applies_to'        => $data['applies_to']   ?? 'all',
                'applies_value'     => $data['applies_value'] ?? null,
            ],
        ]);

        return redirect()->route('admin.leave-policies.index')->with('success', 'Policy diperbarui.');
    }

    public function destroy(LeavePolicy $leave_policy)
    {
        $leave_policy->delete();
        return back()->with('success', 'Policy dihapus.');
    }

    /** Helper Method Test */

    private function ensureNoOverlap(array $data, ?LeavePolicy $ignore = null): void
{
    $appliesTo   = $data['applies_to']  ?? 'all';
    $appliesVal  = $data['applies_value'] ?? null;
    $start       = $data['effective_start'];
    $end         = $data['effective_end'] ?? null;

    $q = LeavePolicy::where('leave_type_id', $data['leave_type_id'])
        ->when($appliesTo === 'all', fn($qq)=>$qq->whereNull('rules->applies_value')->where('rules->applies_to', 'all'))
        ->when($appliesTo !== 'all', fn($qq)=>$qq->where('rules->applies_to', $appliesTo)->where('rules->applies_value', $appliesVal));

    if ($ignore) $q->where('id','<>',$ignore->id);

    // overlap check: (existing.start <= new.end) AND (existing.end IS NULL OR existing.end >= new.start)
    $q->where(function($qq) use ($start,$end){
        $qq->where('effective_start','<=', $end ?? $start)
           ->where(function($w) use ($start){
               $w->whereNull('effective_end')->orWhere('effective_end','>=',$start);
           });
    });

    if ($q->exists()) {
        abort(422, 'Periode kebijakan bertumpuk untuk kombinasi tipe cuti & cakupan yang sama.');
    }
}

}
