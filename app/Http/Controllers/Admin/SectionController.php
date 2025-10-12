<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Position, Department, Section, Employee};
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class SectionController extends Controller
{
    public function index(Request $request)
    {   
        $q = $request->get('q');
        $deptId = $request->integer('department_id');
        $sections = Section::with(['department','positions'])
            ->withCount('positions')
            ->when($q, fn($qr)=>$qr->where('name','like',"%{$q}%"))
            ->when($deptId, fn($qr)=>$qr->where('department_id',$deptId))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
        $departments = Department::all();
        $positions   = Position::all();

        $editSection = null;
        if ($request->filled('edit')) {
            $editSection = Section::with('positions')->findOrFail($request->edit);
        }

        $sections = Section::with(['department', 'positions'])
            ->withCount('positions')
            ->when($request->department_id, fn ($q) => $q->where('department_id', $request->department_id))
            ->orderBy('name')
            ->get();

        return view('admin.pages.sections.index', compact('departments', 'positions', 'sections', 'editSection'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'position_ids'  => 'nullable|array',
            'position_ids.*'=> 'exists:positions,id',
        ]);

        $exists = Section::where('name', $request->name)
            ->where('department_id', $request->department_id)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Seksi dengan nama tersebut sudah ada di departemen yang sama.');
        }

        $section = Section::create([
            'name'          => $request->name,
            'department_id' => $request->department_id,
        ]);

        if ($request->filled('position_ids')) {
            $section->positions()->sync($request->position_ids);
        }

        return redirect()
            ->route('admin.sections.index', ['department_id' => $request->department_id])
            ->with('highlight_id', $section->id)
            ->with('success', 'Seksi berhasil ditambahkan.');
    }

    public function update(Request $request, Section $section)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'position_ids'  => 'nullable|array',
            'position_ids.*'=> 'exists:positions,id',
        ]);

        $exists = Section::where('name', $request->name)
            ->where('department_id', $request->department_id)
            ->where('id', '!=', $section->id)
            ->exists();

        if ($exists) {
            return redirect()->route('admin.sections.index', ['edit' => $section->id, 'department_id' => $request->department_id])
                ->withInput()
                ->with('error', 'Seksi dengan nama tersebut sudah ada di departemen ini.');
        }

        $section->update([
            'name'          => $request->name,
            'department_id' => $request->department_id,
        ]);

        $section->positions()->sync($request->position_ids ?? []);

        return redirect()
            ->route('admin.sections.index', ['department_id' => $request->department_id])
            ->with('highlight_id', $section->id)
            ->with('success', 'Perubahan berhasil disimpan.');
    }

    public function destroy(Section $section)
    {
        // Guard sisi server (penting meski UI dikunci)
        $related = [];

        if ($section->positions()->exists()) {
            $related[] = 'posisi';
        }
        // Jika ada relasi karyawan → cek juga
        if (class_exists(Employee::class) && Employee::where('section_id', $section->id)->exists()) {
            $related[] = 'karyawan';
        }

        if (!empty($related)) {
            return back()->with('error', 'Tidak dapat menghapus karena masih terkait: ' . implode(' & ', $related) . '. Lepaskan terlebih dahulu.');
        }

        try {
            // Pastikan pivot bersih jika constraint belum cascade
            $section->positions()->detach();

            $section->delete();
            return redirect()->route('admin.sections.index')->with('success', 'Seksi berhasil dihapus.');
        } catch (QueryException $e) {
            // fallback bila ada FK lain yang menahan
            return back()->with('error', 'Gagal menghapus (terkait data lain). Lepaskan relasi terlebih dahulu.');
        }
    }
    public function byDepartment(\Illuminate\Http\Request $request)
{
    $id = (int) $request->query('department_id', 0);

    if ($id <= 0) {
        // Tetap 200 supaya fetch tidak gagal; data kosong saja
        return response()->json(['data' => [], 'meta' => ['reason' => 'missing department_id']]);
    }

    $sections = \App\Models\Section::query()
        ->where('department_id', $id)
        ->orderBy('name')
        ->get(['id','name']);

    return response()->json([
        'data' => $sections,
        'meta' => ['count' => $sections->count()],
    ]);
}

}
