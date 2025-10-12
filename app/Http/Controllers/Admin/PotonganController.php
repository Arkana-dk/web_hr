<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Potongan;
use Illuminate\Http\Request;

class PotonganController extends Controller
{
    public function index()
    {
        $potongan = Potongan::all();
        return view('admin.pages.potongan', compact('potongan'));
    }

    public function create()
    {
        return view('admin.pages.potongan-create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);

        Potongan::create($request->only('name', 'amount'));

        return redirect()->route('admin.potongan.index')->with('success', 'Potongan berhasil ditambahkan.');
    }

    public function edit(Potongan $potongan)
    {
        return view('admin.pages.potongan-edit', compact('potongan'));
    }

    public function update(Request $request, Potongan $potongan)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);

        $potongan->update($request->only('name', 'amount'));

        return redirect()->route('admin.potongan.index')->with('success', 'Potongan berhasil diperbarui.');
    }

    public function destroy(Potongan $potongan)
    {
        $potongan->delete();

        return redirect()->route('admin.potongan.index')->with('success', 'Potongan berhasil dihapus.');
    }
}
