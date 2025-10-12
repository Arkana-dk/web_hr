<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PositionController extends Controller
{
    /**
     * GET  /api/admin/positions
     */
    public function index(Request $request)
    {
        $query = Position::with('department')->withCount('employees');

        if ($deptId = $request->query('department_id')) {
            $query->where('department_id', $deptId);
        }

        $positions = $query->get();

        return response()->json([
            'data' => $positions
        ]);
    }

    /**
     * GET  /api/admin/positions/{position}
     */
    public function show(Position $position)
    {
        $position->load(['department', 'employees.recruitment', 'employees.department']);

        return response()->json([
            'data' => $position
        ]);
    }

    /**
     * POST /api/admin/positions
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name'          => [
                'required','string','max:255',
                Rule::unique('positions','name')
                    ->where('department_id', $request->department_id),
            ],
        ]);

        $position = Position::create($data);

        return response()->json([
            'message' => 'Position created',
            'data'    => $position
        ], 201);
    }

    /**
     * PUT  /api/admin/positions/{position}
     */
    public function update(Request $request, Position $position)
    {
        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name'          => [
                'required','string','max:255',
                Rule::unique('positions','name')
                    ->where('department_id', $request->department_id)
                    ->ignore($position->id),
            ],
        ]);

        $position->update($data);

        return response()->json([
            'message' => 'Position updated',
            'data'    => $position
        ]);
    }

    /**
     * DELETE /api/admin/positions/{position}
     */
    public function destroy(Position $position)
    {
        if ($position->employees()->exists()) {
            return response()->json([
                'message' => 'Cannot delete: employees exist'
            ], 422);
        }

        $position->delete();

        return response()->json([
            'message' => 'Position deleted'
        ]);
    }
}
