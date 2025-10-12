<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Position;
use App\Models\Section;
use App\Models\Department;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        $positions = Position::with(['sections.department'])->withCount('employees')->get();
        $sections = Section::with('department')->get();
        $departments = Department::all();

        return view('admin.pages.position.index', compact('positions', 'sections', 'departments'));

    }

    public function show(Request $request, Position $position)
    {
        $search = $request->input('search');

        $query = $position->employees()
            ->with('recruitment', 'department')
            ->when($search, function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('recruitment', function($q2) use ($search) {
                      $q2->where('phone', 'like', "%{$search}%");
                  });
            });

        $employees = $query->paginate(10)->appends(compact('search'));

        return view('admin.pages.position.show', compact('position', 'employees', 'search'));
    }

    public function edit(Position $position)
    {
        $sections = Section::with('department')->get();
        return view('admin.pages.position.edit', [
            'position' => $position,
            'sections' => $sections
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255|unique:positions,name',
            'section_ids'  => 'required|array',
            'section_ids.*'=> 'exists:sections,id',
        ]);

        $position = Position::create([
            'name' => $data['name'],
        ]);

        $position->sections()->sync($data['section_ids']);

        return back()->with('success', 'Position berhasil ditambahkan.');
    }

    public function update(Request $request, Position $position)
    {
        $data = $request->validate([
            'name'         => [
                'required', 'string', 'max:255',
                Rule::unique('positions', 'name')->ignore($position->id),
            ],
            'section_ids'  => 'required|array',
            'section_ids.*'=> 'exists:sections,id',
        ]);

        $position->update(['name' => $data['name']]);
        $position->sections()->sync($data['section_ids']);

        return redirect()
            ->route('admin.positions.index')
            ->with('success', 'Position berhasil diperbarui.');
    }

    public function destroy(Position $position)
    {
        if ($position->employees()->exists()) {
            return back()->withErrors([
                'error' => 'Tidak bisa menghapus: masih ada pegawai di posisi ini.'
            ]);
        }

        $position->sections()->detach();
        $position->delete();

        return redirect()
            ->route('admin.positions.index')
            ->with('success', 'Position berhasil dihapus.');
    }
    public function bySections(\Illuminate\Http\Request $request)
{
    // dukung baik 'section_ids' (array) maupun 'section_ids[]'
    $ids = $request->query('section_ids', $request->query('section_ids', []));
    if (!is_array($ids)) { $ids = [$ids]; }
    $ids = array_values(array_unique(array_map('intval', $ids)));
    $ids = array_filter($ids, fn($v) => $v > 0);

    if (empty($ids)) {
        return response()->json(['data' => [], 'meta' => ['reason' => 'missing section_ids']]);
    }

    $positions = \App\Models\Position::query()
        ->whereHas('sections', function ($q) use ($ids) {
            $q->whereIn('sections.id', $ids);
        })
        ->orderBy('name')
        ->get(['id','name'])
        ->unique('id')
        ->values();

    return response()->json([
        'data' => $positions,
        'meta' => ['count' => $positions->count()],
    ]);
}

}
