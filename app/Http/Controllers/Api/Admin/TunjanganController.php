<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tunjangan;
use Illuminate\Http\Request;

class TunjanganController extends Controller
{
    /** GET /api/admin/tunjangans */
    public function index()
    {
        return response()->json(['data'=> Tunjangan::all()]);
    }

    /** POST /api/admin/tunjangans */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);

        $t = Tunjangan::create($data);
        return response()->json(['message'=>'Tunjangan created','data'=>$t],201);
    }

    /** GET /api/admin/tunjangans/{tunjangan} */
    public function show(Tunjangan $tunjangan)
    {
        return response()->json(['data'=>$tunjangan]);
    }

    /** PUT /api/admin/tunjangans/{tunjangan} */
    public function update(Request $request, Tunjangan $tunjangan)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);
        $tunjangan->update($data);
        return response()->json(['message'=>'Tunjangan updated','data'=>$tunjangan]);
    }

    /** DELETE /api/admin/tunjangans/{tunjangan} */
    public function destroy(Tunjangan $tunjangan)
    {
        $tunjangan->delete();
        return response()->json(['message'=>'Tunjangan deleted']);
    }
}
