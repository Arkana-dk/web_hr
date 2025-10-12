<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShiftGroup;
use App\Models\Employee;
use Illuminate\Http\Request;

class ShiftGroupController extends Controller
{
    /** GET /api/admin/shift-groups */
    public function index()
    {
        return response()->json(['data' => ShiftGroup::with(['shift','employees'])->get()]);
    }

    /** POST /api/admin/shift-groups */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|unique:shift_groups',
            'shift_id' => 'required|exists:shifts,id',
        ]);

        $group = ShiftGroup::create($data);

        return response()->json(['message'=>'ShiftGroup created','data'=>$group], 201);
    }

    /** GET /api/admin/shift-groups/{shiftGroup} */
    public function show(ShiftGroup $shiftGroup)
    {
        $shiftGroup->load(['shift','employees.department','employees.position']);
        return response()->json(['data'=>$shiftGroup]);
    }

    /** PUT /api/admin/shift-groups/{shiftGroup} */
    public function update(Request $request, ShiftGroup $shiftGroup)
    {
        $data = $request->validate([
            'name'     => 'required|string|unique:shift_groups,name,'.$shiftGroup->id,
            'shift_id' => 'required|exists:shifts,id',
        ]);

        $shiftGroup->update($data);

        return response()->json(['message'=>'ShiftGroup updated','data'=>$shiftGroup]);
    }

    /** DELETE /api/admin/shift-groups/{shiftGroup} */
    public function destroy(ShiftGroup $shiftGroup)
    {
        $shiftGroup->employees()->detach();
        $shiftGroup->delete();
        return response()->json(['message'=>'ShiftGroup deleted']);
    }

    /** POST /api/admin/shift-groups/{shiftGroup}/employees */
    public function attachEmployee(Request $request, ShiftGroup $shiftGroup)
    {
        $empId = $request->validate(['employee_id'=>'required|exists:employees,id'])['employee_id'];
        if (! $shiftGroup->employees()->where('employee_id',$empId)->exists()) {
            $shiftGroup->employees()->attach($empId);
        }
        return response()->json(['message'=>'Employee attached']);
    }

    /** DELETE /api/admin/shift-groups/{shiftGroup}/employees/{employee} */
    public function detach(ShiftGroup $shiftGroup, Employee $employee)
    {
        $shiftGroup->employees()->detach($employee->id);
        return response()->json(['message'=>'Employee detached']);
    }

    /** POST /api/admin/shift-groups/{shiftGroup}/move-employee */
    public function moveEmployee(Request $request, ShiftGroup $shiftGroup)
    {
        $data = $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'target_group_id' => 'required|exists:shift_groups,id',
        ]);
        $shiftGroup->employees()->detach($data['employee_id']);
        ShiftGroup::find($data['target_group_id'])
                  ->employees()->attach($data['employee_id']);

        return response()->json(['message'=>'Employee moved']);
    }
}
