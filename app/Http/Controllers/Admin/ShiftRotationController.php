<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShiftRotation;
use App\Models\Group;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftRotationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shiftRotations = ShiftRotation::with(['group', 'shift'])
            ->orderBy('group_id')
            ->orderBy('order')
            ->get();

        return view('admin.pages.shift-rotations.index', compact('shiftRotations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $groups = Group::all();
        $shifts = Shift::all();

        return view('admin.pages.shift-rotations.create', compact('groups', 'shifts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:groups,id',
            'order' => 'required|integer|min:1',
            'shift_id' => 'nullable|exists:shifts,id',
        ]);

        ShiftRotation::create([
            'group_id' => $request->group_id,
            'order' => $request->order,
            'shift_id' => $request->shift_id,
        ]);

        return redirect()->route('admin.shift-rotations.index')
            ->with('success', 'Shift Rotation berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $rotation = ShiftRotation::findOrFail($id);
        $groups = Group::all();
        $shifts = Shift::all();

        return view('admin.pages.shift-rotations-edit', compact('rotation', 'groups', 'shifts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $rotation = ShiftRotation::findOrFail($id);

        $request->validate([
            'group_id' => 'required|exists:groups,id',
            'order' => 'required|integer|min:1',
            'shift_id' => 'nullable|exists:shifts,id',
        ]);

        $rotation->update([
            'group_id' => $request->group_id,
            'order' => $request->order,
            'shift_id' => $request->shift_id,
        ]);

        return redirect()->route('admin.shift-rotations.index')
            ->with('success', 'Shift Rotation berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $rotation = ShiftRotation::findOrFail($id);
        $rotation->delete();

        return redirect()->route('admin.shift-rotations.index')
            ->with('success', 'Shift Rotation berhasil dihapus.');
    }
}
