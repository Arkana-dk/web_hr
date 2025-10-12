<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Potongan;
use App\Models\EmployeeDeduction;
use Illuminate\Http\Request;

class EmployeeDeductionController extends Controller
{
    /** GET /api/admin/employee-deductions */
    public function index(Request $request)
    {
        $q = Employee::withCount('deductions')->with(['department','position']);
        if ($d = $request->query('department')) {
            $q->where('department_id', $d);
        }
        if ($s = $request->query('search')) {
            $q->where('name','like',"%{$s}%");
        }
        $emps = $q->orderBy('name')->paginate(10);
        return response()->json($emps);
    }

    /** POST /api/admin/employee-deductions */
    public function store(Request $r)
    {
        $r->validate([
            'employee_id'    => 'required|exists:employees,id',
            'potongan_ids'   => 'required|array|min:1',
            'potongan_ids.*' => 'exists:potongan,id',
        ]);
        foreach($r->potongan_ids as $pid) {
            EmployeeDeduction::firstOrCreate(
                ['employee_id'=>$r->employee_id,'potongan_id'=>$pid],
                ['amount'=>Potongan::find($pid)->amount]
            );
        }
        return response()->json(['message'=>'Deductions saved']);
    }

    /** GET /api/admin/employee-deductions/{employee} */
    public function show($employee_id)
    {
        $emp = Employee::with(['department','position','deductions.potongan'])
                       ->findOrFail($employee_id);
        return response()->json(['data'=>$emp]);
    }

    /** PUT /api/admin/employee-deductions/{employee} */
    public function update(Request $r, $employee_id)
    {
        $r->validate([
            'potongan_ids'   => 'required|array|min:1',
            'potongan_ids.*' => 'exists:potongan,id',
        ]);
        $emp = Employee::findOrFail($employee_id);
        $emp->deductions()->delete();
        foreach($r->potongan_ids as $pid) {
            EmployeeDeduction::create([
                'employee_id'=>$employee_id,
                'potongan_id'=>$pid,
                'amount'=>Potongan::find($pid)->amount
            ]);
        }
        return response()->json(['message'=>'Deductions updated']);
    }

    /** DELETE /api/admin/employee-deductions/{ed} */
    public function destroy(EmployeeDeduction $ed)
    {
        $ed->delete();
        return response()->json(['message'=>'Deduction deleted']);
    }
}
