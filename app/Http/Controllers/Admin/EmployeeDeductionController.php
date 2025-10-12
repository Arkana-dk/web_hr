<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Potongan;
use App\Models\EmployeeDeduction;
use App\Models\Department;
use Illuminate\Http\Request;

class EmployeeDeductionController extends Controller
{
    public function index(Request $request)
    {
        $q = Employee::withCount('deductions')->with(['department','position']);
        if ($d=$request->department) $q->where('department_id',$d);
        if ($s=$request->search)     $q->where('name','like',"%{$s}%");
        $employees   = $q->orderBy('name')->paginate(10)->withQueryString();
        $departments = Department::orderBy('name')->get();
        return view('admin.pages.employee-deductions-index', compact('employees','departments'));
    }

    public function create(Request $request)
    {
        $selectedEmployee = Employee::findOrFail($request->employee_id);
        $potongans        = Potongan::orderBy('name')->get();
        return view('admin.pages.employee-deductions-create', compact('selectedEmployee','potongans'));
    }

    public function store(Request $r)
    {
        $r->validate([
            'employee_id'    => 'required|exists:employees,id',
            'potongan_ids'   => 'required|array|min:1',
            'potongan_ids.*' => 'exists:potongan,id',
        ]);
        foreach($r->potongan_ids as $pid){
            EmployeeDeduction::firstOrCreate(
                ['employee_id'=>$r->employee_id,'potongan_id'=>$pid],
                ['amount'=>Potongan::find($pid)->amount]
            );
        }
        return redirect()->route('admin.employee-deductions.index')
                         ->with('success','Potongan berhasil disimpan.');
    }

    public function show($employee_id)
    {
        $employee = Employee::with(['department','position','deductions.potongan'])
                            ->findOrFail($employee_id);
        return view('admin.pages.employee-deductions-show', compact('employee'));
    }

    public function edit($employee_id)
    {
        $selectedEmployee = Employee::with('deductions.potongan')
                                    ->findOrFail($employee_id);
        $potongans        = Potongan::orderBy('name')->get();
        return view('admin.pages.employee-deductions-edit', compact('selectedEmployee','potongans'));
    }

    public function update(Request $r, $employee_id)
    {
        $r->validate([
            'potongan_ids'   => 'required|array|min:1',
            'potongan_ids.*' => 'exists:potongan,id',
        ]);
        $emp = Employee::findOrFail($employee_id);
        $emp->deductions()->delete();
        foreach($r->potongan_ids as $pid){
            EmployeeDeduction::create([
                'employee_id'=>$employee_id,
                'potongan_id'=>$pid,
                'amount'=>Potongan::find($pid)->amount
            ]);
        }
        return redirect()->route('admin.employee-deductions.index')
                         ->with('success','Potongan berhasil diperbarui.');
    }

    public function destroy(EmployeeDeduction $ed)
    {
        $ed->delete();
        return back()->with('success','Potongan dihapus.');
    }
}
