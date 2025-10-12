<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recruitment;
use App\Models\Employee;
use App\Models\User;
use App\Models\Department;
use App\Models\Group;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RecruitmentController extends Controller
{
    /**
     * Show form untuk menambah rekrutmen baru.
     */
    public function create()
    {
        
        $departments = Department::with('positions')->get();
        $deptPositions = $departments
            ->mapWithKeys(fn($dept) => [
                $dept->id => $dept->positions
                    ->map(fn($pos) => ['id' => $pos->id, 'name' => $pos->name])
                    ->all()
            ])
            ->all();
        $sections = Section::all();
        $groups = Group::all();

        return view('admin.pages.recruitment-create', compact('departments', 'deptPositions', 'groups','sections'));

    }

    /**
     * Simpan data rekrutmen baru.
     */
    public function store(Request $request)
{
    $data = $request->validate([
        'name'                => 'required|string|max:255',
        'nik'                 => 'required|string|unique:employees,nik',
        'email'               => 'required|email|unique:users,email|unique:employees,email',
        'password'            => 'required|string|min:6',
        'title'               => 'nullable|string|max:255',
        'address'             => 'required|string',
        'place_of_birth'      => 'required|string|max:255',
        'date_of_birth'       => 'required|date',
        'kk_number'           => 'required|string|max:255',
        'religion'            => 'required|string|max:100',
        'gender'              => 'required|in:Laki-laki,Perempuan',
        'department_id'       => 'required|exists:departments,id',
        'position_id'         => 'required|exists:positions,id',
        'section_id'          => 'nullable|exists:sections,id',
        'group_id'            => 'nullable|exists:groups,id',
        'tmt'                 => 'required|date',
        'contract_end_date'   => 'required|date|after_or_equal:tmt',
        'phone'               => 'required|string|max:20',
        'marital_status'      => 'required|in:Sudah Kawin,Belum Kawin',
        'education'           => 'required|string|max:255',
        'salary'              => 'required|numeric',
        'photo'               => 'nullable|mimes:jpg,jpeg,png|max:2048',
        'bank_account_name'   => 'required|string|max:255',
        'bank_account_number' => 'required|string|max:50',
    ]);

    if ($request->hasFile('photo')) {
        $data['photo'] = $request->file('photo')->store('recruitments', 'public');
    }

    DB::transaction(function() use ($data) {
        // Buat User
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'photo'    => $data['photo'] ?? null,
            'role'     => 'user',
        ]);

        // Buat Employee
        $lastId = \App\Models\Employee::max('id') + 1;
        $data['employee_number'] = 'EMP-' . str_pad($lastId, 5, '0', STR_PAD_LEFT);

        $employee = Employee::create([
            'user_id'             => $user->id,
            'name'                => $data['name'],
            'nik'                 => $data['nik'],
            'email'               => $data['email'],
            'gender'              => $data['gender'],
            'title'               => $data['title'] ?? null,
            'photo'               => $data['photo'] ?? null,
            'date_of_birth'       => $data['date_of_birth'],
            'tmt'                 => $data['tmt'],
            'contract_end_date'   => $data['contract_end_date'],
            'phone'               => $data['phone'],
            'department_id'       => $data['department_id'],
            'position_id'         => $data['position_id'],
            'group_id'            => $data['group_id'] ?? null, // ✅ baru
            'bank_account_name'   => $data['bank_account_name'],
            'bank_account_number' => $data['bank_account_number'],
            'employee_number'     => $data['employee_number'],
        ]);

        // Buat Recruitment
        Recruitment::create([
            'user_id'             => $user->id,
            'employee_id'         => $employee->id,
            'name'                => $data['name'],
            'nik'                 => $data['nik'],
            'email'               => $data['email'],
            'password'            => Hash::make($data['password']),
            'phone'               => $data['phone'],
            'gender'              => $data['gender'],
            'place_of_birth'      => $data['place_of_birth'],
            'date_of_birth'       => $data['date_of_birth'],
            'kk_number'           => $data['kk_number'],
            'religion'            => $data['religion'],
            'address'             => $data['address'],
            'marital_status'      => $data['marital_status'],
            'education'           => $data['education'],
            'tmt'                 => $data['tmt'],
            'contract_end_date'   => $data['contract_end_date'],
            'salary'              => $data['salary'],
            'photo'               => $data['photo'] ?? null,
            'department_id'       => $data['department_id'],
            'position_id'         => $data['position_id'],
            'section_id'          => $data['section_id'] ?? null,
            'group_id'            => $data['group_id'] ?? null,
            'title'               => $data['title'] ?? null,
            'bank_account_name'   => $data['bank_account_name'],
            'bank_account_number' => $data['bank_account_number'],
        ]);
    });


    return redirect()->route('admin.employees.index')
                     ->with('success', 'Rekrutmen berhasil ditambahkan!');
}

}
