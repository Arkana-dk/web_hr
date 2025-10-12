<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShiftGroup;
use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftGroupController extends Controller
{
    /**
     * Display a listing of the shift groups.
     */
    public function index()
    {
        $groups = ShiftGroup::with(['shift','employees'])->get();
        return view('admin.pages.shift-groups', compact('groups'));
    }

    /**
     * Show the form for creating a new shift group.
     */
    public function create()
    {
        $shifts = Shift::orderBy('start_time')->get();
        return view('admin.pages.shift-group-create', compact('shifts'));
    }

    /**
     * Store a newly created shift group in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|unique:shift_groups',
            'shift_id' => 'required|exists:shifts,id',
        ]);

        ShiftGroup::create($request->only('name','shift_id'));

        return redirect()->route('admin.shift-groups.index')
                         ->with('success', 'Group shift berhasil dibuat');
    }

    /**
     * Display the specified shift group.
     */
    public function show(Request $request, ShiftGroup $shiftGroup)
    {
        $group = $shiftGroup->load(['shift','employees.department','employees.position']);
        $otherGroups = ShiftGroup::where('id', '!=', $group->id)->get();

        $employees = $group->employees()
                           ->when($request->query('search'), function($q, $search) {
                               $q->where('name','like',"%{$search}%")
                                 ->orWhereHas('department', function($q2) use ($search) {
                                     $q2->where('name','like',"%{$search}%");
                                 })
                                 ->orWhereHas('position', function($q2) use ($search) {
                                     $q2->where('name','like',"%{$search}%");
                                 });
                           })
                           ->paginate(10)
                           ->appends($request->only('search'));

        return view('admin.pages.shift-group-show', compact('group','employees','otherGroups'));
    }

    /**
     * Show the form for editing the specified shift group.
     */
    public function edit(ShiftGroup $shiftGroup)
    {
        $group  = $shiftGroup;
        $shifts = Shift::orderBy('start_time')->get();
        return view('admin.pages.shift-group-edit', compact('group','shifts'));
    }

    /**
     * Update the specified shift group in storage.
     */
    public function update(Request $request, ShiftGroup $shiftGroup)
    {
        $request->validate([
            'name'     => 'required|string|unique:shift_groups,name,' . $shiftGroup->id,
            'shift_id' => 'required|exists:shifts,id',
        ]);

        $shiftGroup->update($request->only('name','shift_id'));

        return redirect()->route('admin.shift-groups.index')
                         ->with('success', 'Group shift berhasil diperbarui');
    }

    /**
     * Remove the specified shift group from storage.
     */
    public function destroy(ShiftGroup $shiftGroup)
    {
        $shiftGroup->employees()->detach();
        $shiftGroup->delete();

        return redirect()->route('admin.shift-groups.index')
                         ->with('success', 'Group shift berhasil dihapus');
    }

    /**
     * Show list of employees available to be attached to the shift group.
     */
    public function select(Request $request, ShiftGroup $shiftGroup)
    {
        $group = $shiftGroup;
        $assigned = $group->employees->pluck('id');
        $query    = Employee::query();

        if ($search = $request->query('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nik',  'like', "%{$search}%")
                  ->orWhere('email','like', "%{$search}%");
            });
        }

        $employees = $query->whereNotIn('id', $assigned)
                           ->paginate(10)
                           ->appends($request->only('search'));

        return view('admin.pages.shift-group-select', compact('group','employees'));
    }

    /**
     * Attach an employee to the shift group.
     */
    public function attachEmployee(ShiftGroup $shiftGroup, Employee $employee)
    {
        if (!$shiftGroup->employees()->where('employee_id', $employee->id)->exists()) {
            $shiftGroup->employees()->attach($employee->id);
        }
        return redirect()->route('admin.shift-groups.show', $shiftGroup)
                         ->with('success', 'Pekerja berhasil ditambahkan');
    }

    /**
     * Detach an employee from the shift group.
     */
    public function detachEmployee(ShiftGroup $shiftGroup, Employee $employee)
    {
        $shiftGroup->employees()->detach($employee->id);
        return redirect()->route('admin.shift-groups.show', $shiftGroup)
                         ->with('success', 'Pekerja berhasil dihapus');
    }

    /**
     * Move an employee from this group to another group.
     */
    public function moveEmployee(Request $request, ShiftGroup $shiftGroup)
    {
        $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'target_group_id' => 'required|exists:shift_groups,id',
        ]);

        $employeeId    = $request->input('employee_id');
        $targetGroupId = $request->input('target_group_id');

        $shiftGroup->employees()->detach($employeeId);
        ShiftGroup::find($targetGroupId)->employees()->attach($employeeId);

        return redirect()->route('admin.shift-groups.show', $shiftGroup)
                         ->with('success', 'Pekerja berhasil dipindahkan ke group lain');
    }
}
