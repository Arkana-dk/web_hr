<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Potongan;
use Illuminate\Http\Request;

class PotonganController extends Controller
{
    /**
     * GET  /api/admin/potongan
     */
    public function index()
    {
        $items = Potongan::all();
        return response()->json(['data' => $items]);
    }

    /**
     * POST /api/admin/potongan
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);

        $item = Potongan::create($data);

        return response()->json([
            'message' => 'Potongan created',
            'data'    => $item
        ], 201);
    }

    /**
     * GET  /api/admin/potongan/{potongan}
     */
    public function show(Potongan $potongan)
    {
        return response()->json(['data' => $potongan]);
    }

    /**
     * PUT  /api/admin/potongan/{potongan}
     */
    public function update(Request $request, Potongan $potongan)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);

        $potongan->update($data);

        return response()->json([
            'message' => 'Potongan updated',
            'data'    => $potongan
        ]);
    }

    /**
     * DELETE /api/admin/potongan/{potongan}
     */
    public function destroy(Potongan $potongan)
    {
        $potongan->delete();

        return response()->json(['message' => 'Potongan deleted']);
    }
}
