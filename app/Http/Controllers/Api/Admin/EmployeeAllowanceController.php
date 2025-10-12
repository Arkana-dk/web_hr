<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmployeeAllowance;
use App\Models\Tunjangan;
use Illuminate\Validation\Rule;

class EmployeeAllowanceController extends Controller
{
    public function index(Request $request)
    {
        $q = EmployeeAllowance::with(['employee','tunjangan']);

        if ($request->filled('employee_id')) {
            $q->where('employee_id',$request->employee_id);
        }

        $perPage = $request->input('per_page', 15);
        $data = $q->paginate($perPage);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'tunjangan_ids'  => 'required|array|min:1',
            'tunjangan_ids.*'=> 'exists:tunjangan,id',
        ]);

        $created = [];
        foreach ($data['tunjangan_ids'] as $tid) {
            $allowance = EmployeeAllowance::firstOrCreate(
                ['employee_id'=>$data['employee_id'],'tunjangan_id'=>$tid],
                ['amount'=>Tunjangan::find($tid)->amount]
            );
            $created[] = $allowance;
        }

        return response()->json($created, 201);
    }

    public function show($id)
    {
        $allowance = EmployeeAllowance::with(['employee','tunjangan'])
            ->findOrFail($id);

        return response()->json($allowance);
    }

    public function update(Request $request, $id)
    {
        $allowance = EmployeeAllowance::findOrFail($id);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $allowance->update(['amount'=>$data['amount']]);
        return response()->json($allowance);
    }

    public function destroy($id)
    {
        $allowance = EmployeeAllowance::findOrFail($id);
        $allowance->delete();
        return response()->json(null, 204);
    }
}
