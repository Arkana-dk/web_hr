<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function index()
    {
        $types = LeaveType::orderBy('name')->paginate(12);
        return view('admin.pages.leave-types.index', compact('types'));
    }

    public function create()
    {
        return view('admin.pages.leave-types.create');
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'code' => 'required|string|max:50|unique:leave_types,code',
            'name' => 'required|string|max:100|unique:leave_types,name',
            'is_paid' => 'nullable|boolean',
            'requires_attachment' => 'nullable|boolean',
        ]);

        $data['is_paid'] = (bool)($data['is_paid'] ?? false);
        $data['requires_attachment'] = (bool)($data['requires_attachment'] ?? false);

        LeaveType::create($data);
        return redirect()->route('admin.leave-types.index')->with('success','Jenis cuti dibuat.');
    }

    public function show(LeaveType $leave_type)
    {
        return view('admin.pages.leave-types.show', ['type' => $leave_type]);
    }

    public function edit(LeaveType $leave_type)
    {
        return view('admin.pages.leave-types.edit', ['type' => $leave_type]);
    }

    public function update(Request $r, LeaveType $leave_type)
    {
        $data = $r->validate([
            'code' => 'required|string|max:50|unique:leave_types,code,'.$leave_type->id,
            'name' => 'required|string|max:100|unique:leave_types,name,'.$leave_type->id,
            'is_paid' => 'nullable|boolean',
            'requires_attachment' => 'nullable|boolean',
        ]);

        $data['is_paid'] = (bool)($data['is_paid'] ?? false);
        $data['requires_attachment'] = (bool)($data['requires_attachment'] ?? false);

        $leave_type->update($data);
        return redirect()->route('admin.leave-types.index')->with('success','Jenis cuti diperbarui.');
    }

    public function destroy(LeaveType $leave_type)
    {
        $leave_type->delete();
        return back()->with('success','Jenis cuti dihapus.');
    }
}
