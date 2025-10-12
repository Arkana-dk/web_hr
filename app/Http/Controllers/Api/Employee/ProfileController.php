<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * GET /api/employee/profile
     * Return the authenticated user and their employee record.
     */
    public function show()
    {
        $user     = Auth::user();
        $employee = $user->employee;

        return response()->json([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                // add other user fields as needed
            ],
            'employee' => [
                'id'            => $employee->id,
                'nik'           => $employee->nik,
                'department_id' => $employee->department_id,
                'position_id'   => $employee->position_id,
                'photo' => $employee->photo_url ?? null,
                // add other employee fields as needed
            ],
        ]);
    }
}
