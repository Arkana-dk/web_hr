<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    /** GET /api/admin/shifts */
    public function index()
    {
        return response()->json(['data' => Shift::orderBy('start_time')->get()]);
    }

    /** POST /api/admin/shifts */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|unique:shifts',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i',
        ]);

        $shift = Shift::create($data);

        return response()->json(['message'=>'Shift created','data'=>$shift], 201);
    }

    /** PUT /api/admin/shifts/{shift} */
    public function update(Request $request, Shift $shift)
    {
        $data = $request->validate([
            'name'       => 'required|string|unique:shifts,name,'.$shift->id,
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i',
        ]);

        $shift->update($data);

        return response()->json(['message'=>'Shift updated','data'=>$shift]);
    }

    /** DELETE /api/admin/shifts/{shift} */
    public function destroy(Shift $shift)
    {
        $shift->delete();
        return response()->json(['message'=>'Shift deleted']);
    }
}
