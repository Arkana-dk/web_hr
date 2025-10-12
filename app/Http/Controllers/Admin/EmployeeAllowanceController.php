<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Tunjangan;
use App\Models\EmployeeAllowance;
use App\Models\Department;
use Illuminate\Http\Request;

class EmployeeAllowanceController extends Controller
{
    public function index(Request $request)
    {
        $q = Employee::with(['department','position','allowances']);
        if ($d=$request->department) $q->where('department_id',$d);
        if ($s=$request->search)     $q->where('name','like',"%{$s}%");
        $employees   = $q->orderBy('name')->paginate(10)->withQueryString();
        $departments = Department::orderBy('name')->get();
        return view('admin.pages.employee-allowances', compact('employees','departments'));
    }

    public function create(Request $request)
    {
        $employees        = Employee::orderBy('name')->get();
        $tunjangans       = Tunjangan::orderBy('name')->get();
        $selectedEmployee = $request->filled('employee_id')
                            ? Employee::find($request->employee_id)
                            : null;
        return view('admin.pages.employee-allowances-create', compact('employees','tunjangans','selectedEmployee'));
    }

    public function store(Request $r)
    {
        $r->validate([
            'employee_id'    => 'required|exists:employees,id',
            'tunjangan_ids'  => 'required|array|min:1',
            'tunjangan_ids.*'=> 'exists:tunjangan,id',
        ]);
        foreach($r->tunjangan_ids as $tid){
            EmployeeAllowance::firstOrCreate(
                ['employee_id'=>$r->employee_id,'tunjangan_id'=>$tid],
                ['amount'=>Tunjangan::find($tid)->amount]
            );
        }
        return redirect()->route('admin.employee-allowances.index')
                         ->with('success','Tunjangan berhasil disimpan.');
    }

    public function show($employee_id)
    {
        $employee = Employee::with(['department','position','allowances.tunjangan'])
                            ->findOrFail($employee_id);
        return view('admin.pages.employee-allowances-show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $selectedEmployee = $employee->load('allowances.tunjangan');
        $tunjangans       = Tunjangan::orderBy('name')->get();
        return view('admin.pages.employee-allowances-edit', compact('selectedEmployee','tunjangans'));
    }

    public function update(Request $r, Employee $employee)
    {
        $r->validate([
            'tunjangan_ids'   => 'required|array|min:1',
            'tunjangan_ids.*' => 'exists:tunjangan,id',
        ]);
        $employee->allowances()->delete();
        foreach($r->tunjangan_ids as $tid){
            EmployeeAllowance::create([
                'employee_id'=>$employee->id,
                'tunjangan_id'=>$tid,
                'amount'=>Tunjangan::find($tid)->amount
            ]);
        }
        return redirect()->route('admin.employee-allowances.index')
                         ->with('success','Tunjangan berhasil diperbarui.');
    }

    public function destroy(EmployeeAllowance $ea)
    {
        $ea->delete();
        return back()->with('success','Tunjangan dihapus.');
    }
}
