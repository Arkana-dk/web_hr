<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $q = Employee::with('recruitment');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(fn($q)=>
                $q->where('name','like',"%$s%")
                  ->orWhere('nik','like',"%$s%")
                  ->orWhere('email','like',"%$s%")
            );
        }
        $perPage = $request->input('per_page', 15);
        $data = $q->paginate($perPage);
        return response()->json($data);
    }

    public function show($id)
    {
        $emp = Employee::with('recruitment')->findOrFail($id);
        return response()->json($emp);
    }

    public function update(Request $request, $id)
    {
        $emp = Employee::findOrFail($id);

        $data = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'nik'       => ['sometimes','string','max:255', Rule::unique('employees','nik')->ignore($emp->id)],
            'email'     => ['sometimes','email','max:255',
                              Rule::unique('users','email')->ignore($emp->user_id),
                              Rule::unique('employees','email')->ignore($emp->id)],
            'password'  => 'sometimes|string|min:6',
            'salary'    => 'sometimes|numeric|min:0',
            // tambahkan field lain sesuai kebutuhan...
        ]);

        // jika password di-update, hash dulu
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $emp->update($data);

        return response()->json($emp);
    }

    public function destroy($id)
    {
        $emp = Employee::findOrFail($id);
        $emp->delete();
        return response()->json(null, 204);
    }
}
