<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recruitment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Employee;

class RecruitmentController extends Controller
{
    /**
     * GET  /api/admin/recruitments
     */
    public function index()
    {
        $items = Recruitment::with(['employee','employee.recruitment'])->get();
        return response()->json(['data' => $items]);
    }

    /**
     * GET  /api/admin/recruitments/{recruitment}
     */
    public function show(Recruitment $recruitment)
    {
        $recruitment->load(['employee','employee.recruitment']);
        return response()->json(['data' => $recruitment]);
    }

    /**
     * POST /api/admin/recruitments
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'nik'                 => 'required|string|unique:employees,nik',
            'email'               => 'required|email|unique:users,email|unique:employees,email',
            'password'            => 'required|string|min:6',
            'address'             => 'required|string',
            'place_of_birth'      => 'required|string|max:255',
            'date_of_birth'       => 'required|date',
            'kk_number'           => 'required|string|max:255',
            'religion'            => 'required|string|max:100',
            'gender'              => 'required|in:Laki-laki,Perempuan',
            'department_id'       => 'required|exists:departments,id',
            'position_id'         => 'required|exists:positions,id',
            'tmt'                 => 'required|date',
            'contract_end_date'   => 'required|date|after_or_equal:tmt',
            'phone'               => 'required|string|max:20',
            'marital_status'      => 'required|in:Sudah Kawin,Belum Kawin',
            'education'           => 'required|string|max:255',
            'salary'              => 'required|numeric',
            'bank_account_name'   => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:50',
            'photo'               => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('recruitments','public');
        }

        DB::transaction(function() use($data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'     => 'user',
                'photo'    => $data['photo'] ?? null,
            ]);

            $emp = Employee::create([
                'user_id'             => $user->id,
                'name'                => $data['name'],
                'nik'                 => $data['nik'],
                'email'               => $data['email'],
                'gender'              => $data['gender'],
                'date_of_birth'       => $data['date_of_birth'],
                'tmt'                 => $data['tmt'],
                'contract_end_date'   => $data['contract_end_date'],
                'phone'               => $data['phone'],
                'department_id'       => $data['department_id'],
                'position_id'         => $data['position_id'],
                'bank_account_name'   => $data['bank_account_name'],
                'bank_account_number' => $data['bank_account_number'],
                'photo'               => $data['photo'] ?? null,
            ]);

            Recruitment::create(array_merge($data, [
                'user_id'     => $user->id,
                'employee_id' => $emp->id,
                'password'    => Hash::make($data['password']),
            ]));
        });

        return response()->json(['message' => 'Recruitment created'], 201);
    }
}
