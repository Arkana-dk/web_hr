<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Employee;
use Illuminate\Http\Request;

class GroupController extends Controller
{
        public function index()
    {
        $groups = Group::withCount('employees')->latest()->get();
        return view('admin.pages.groups.index', compact('groups'));
    }


    public function create()
    {
        return view('admin.pages.groups.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:groups,name',
        ]);

        Group::create($request->only('name'));

        return redirect()->route('admin.groups.index')->with('success', 'Group berhasil ditambahkan.');
    }

        public function edit(Group $group)
    {
        return view('admin.pages.groups.edit', compact('group'));
    }

        public function show(Request $request, Group $group)
    {
        $search = $request->input('search');

        $availableEmployees = collect(); // kosong by default

        if ($search) {
            $availableEmployees = Employee::where(function ($query) use ($search) {
                    $query->where('name', 'like', "%$search%")
                        ->orWhere('employee_number', 'like', "%$search%");
                })
                ->whereNull('group_id')
                ->with('department', 'position')
                ->get();
        }

        $employees = $group->employees()->with('department', 'position')->get();

        return view('admin.pages.groups.show', compact('group', 'employees', 'availableEmployees', 'search'));
    }


public function addEmployee(Request $request, Group $group)
{
    $request->validate([
        'employee_id' => 'required|exists:employees,id',
    ]);

    $employee = Employee::find($request->employee_id);
    $employee->group_id = $group->id;
    $employee->save();

    return back()->with('success', 'Employee berhasil ditambahkan ke group.');
}

public function removeEmployee(Group $group, Employee $employee)
{
    if ($employee->group_id !== $group->id) {
        return back()->with('error', 'Employee tidak termasuk dalam group ini.');
    }

    $employee->group_id = null;
    $employee->save();

    return back()->with('success', 'Employee berhasil dihapus dari group.');
}


    public function update(Request $request, Group $group)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:groups,name,' . $group->id,
        ]);

        $group->update($request->only('name'));

        return redirect()->route('admin.groups.index')->with('success', 'Group berhasil diperbarui.');
    }

    public function destroy(Group $group)
    {
        $group->delete();
        return back()->with('success', 'Group berhasil dihapus.');
    }
    // app/Models/Group.php

        public function employees()
        {
            return $this->hasMany(Employee::class);
        }

}
