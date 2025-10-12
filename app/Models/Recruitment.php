<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Employee;

class Recruitment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'employee_id',
        'name',
        'address',
        'place_of_birth',
        'date_of_birth',
        'kk_number',
        'religion',
        'gender',
        'department',
        'position',
        'title',
        'tmt',
        'contract_end_date',
        'salary',
        'photo',
        'email',
        'password',
        'phone',
        'marital_status',
        'education',
        'bank_account_name',
        'bank_account_number',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth'       => 'date',
        'tmt'                 => 'date',
        'contract_end_date'   => 'date',
        'salary'              => 'decimal:2',
    ];

    /**
     * Recruitment → User
     *
     * Each recruitment is created by one user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Recruitment → Employee
     *
     * Each recruitment record belongs to one employee.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
    public function section()
    {
    return $this->belongsTo(\App\Models\Section::class);
    }

    public function group()
    {
        return $this->belongsTo(\App\Models\Group::class);
    }
        /**
     * Recruitment → Employee
     *
     * Import Function.
     */

    public function convertToEmployee()
    {
        // Jangan double-convert kalau sudah jadi employee
        if ($this->employee) {
            return $this->employee;
        }

        // 1. Buat User
        $user = \App\Models\User::create([
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => \Hash::make('password123'), // Default password (bisa diganti)
            'photo'    => $this->photo ?? null,
        ]);

        // 2. Buat Employee
        $employee = \App\Models\Employee::create([
            'name'                => $this->name,
            'nik'                 => 'AUTO-' . now()->timestamp, // NIK default, bisa diatur
            'email'               => $this->email,
            'gender'              => $this->gender,
            'date_of_birth'       => $this->date_of_birth,
            'tmt'                 => now(), // atau ambil dari recruitment jika ada
            'contract_end_date'   => now()->addYear(), // default 1 tahun
            'department_id'       => $this->department_id,
            'position_id'         => $this->position_id,
            'title'               => null,
            'bank_account_name'   => $this->bank_account_name ?? '-',
            'bank_account_number' => $this->bank_account_number ?? '-',
            'photo'               => $this->photo ?? null,
            'user_id'             => $user->id,
            'recruitment_id'      => $this->id,
            'group_id'            => null,
        ]);

        return $employee;
    }


}
