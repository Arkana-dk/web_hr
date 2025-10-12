<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tunjangan;
use Illuminate\Http\Request;

class TunjanganController extends Controller
{
    public function index()
    {
        $tunjangan = Tunjangan::all();
        return view('admin.pages.tunjangan', compact('tunjangan'));
    }

    public function create()
    {
        return view('admin.pages.tunjangan-create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);

        Tunjangan::create([
            'name' => $request->name,
            'amount' => $request->amount,
        ]);

        return redirect()->route('admin.tunjangan.index')->with('success', 'Tunjangan berhasil ditambahkan.');
    }

    public function edit(Tunjangan $tunjangan)
    {
        return view('admin.pages.tunjangan-edit', compact('tunjangan'));
    }

    public function update(Request $request, Tunjangan $tunjangan)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);

        $tunjangan->update([
            'name' => $request->name,
            'amount' => $request->amount,
        ]);

        return redirect()->route('admin.tunjangan.index')->with('success', 'Tunjangan berhasil diperbarui.');
    }

    public function destroy(Tunjangan $tunjangan)
    {
        $tunjangan->delete();

        return redirect()->route('admin.tunjangan.index')->with('success', 'Tunjangan berhasil dihapus.');
    }
}
