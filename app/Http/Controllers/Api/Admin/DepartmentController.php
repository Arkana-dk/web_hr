<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount('employees')->get();
        return response()->json($departments);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
        ]);

        $dept = Department::create(['name'=>$data['name']]);
        return response()->json($dept, 201);
    }

    public function show($id)
    {
        $dept = Department::withCount('employees')->findOrFail($id);
        return response()->json($dept);
    }

    public function update(Request $request, $id)
    {
        $dept = Department::findOrFail($id);
        $data = $request->validate([
            'name' => [
                'required','string','max:255',
                Rule::unique('departments','name')->ignore($dept->id),
            ],
        ]);

        $dept->update(['name'=>$data['name']]);
        return response()->json($dept);
    }

    public function destroy($id)
    {
        $dept = Department::findOrFail($id);

        if ($dept->employees()->exists()) {
            return response()->json([
                'message'=>'Masih ada pegawai di department ini'
            ], 422);
        }

        $dept->delete();
        return response()->json(null, 204);
    }
}
