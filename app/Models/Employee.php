<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;


class Employee extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes.
     * NOTE: tambahkan pay_group_id (untuk payroll Pay Group).
     */
    protected $fillable = [
        'user_id',
        'name',
        'national_identity_number',
        'family_number_card',
        'email',
        'gender',
        'title',
        'photo',
        'phone',
        'department_id',
        'position_id',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'salary',          // legacy/basic salary (fallback)
        'group_id',        // organisasi (bukan Pay Group)
        'section_id',
        'date_of_birth',
        'tmt',
        'contract_end_date',
        'employee_number',
        'address',
        'religion',
        'marital_status',
        'education',
        'place_of_birth',
        'pay_group_id',    // ⬅️ penting untuk payroll
        'dependents_count' // naru untuk pph21
    ];

    /**
     * Attribute casts (aman untuk tanggal & angka).
     */
    protected $casts = [
        'salary'           => 'float',
        'date_of_birth'    => 'date',
        'tmt'              => 'date',
        'contract_end_date'=> 'date',
    ];
    public function getTaxStatusAttribute(): string
    {
        $dependents = min((int)($this->dependents_count ?? 0), 3); // maksimal 3

        if ($this->marital_status === 'Sudah Kawin') {
            return 'K/'.$dependents;
        }

        return 'TK/'.$dependents;
    }

    /* =======================
     |  Relations
     |=======================*/
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recruitment(): HasOne
    {
        return $this->hasOne(Recruitment::class, 'employee_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceRequests(): HasMany
    {
        return $this->hasMany(\App\Models\AttendanceRequest::class);
    }

    public function overtimeRequests(): HasMany
    {
        return $this->hasMany(OvertimeRequest::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function allowances(): HasMany
    {
        return $this->hasMany(EmployeeAllowance::class);
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(EmployeeDeduction::class);
    }

    public function shiftChangeRequests(): HasMany
    {
        return $this->hasMany(ShiftChangeRequest::class);
    }

    /** Organisational grouping (bukan Pay Group payroll). */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Section::class);
    }

    public function workSchedules(): HasMany
    {
        return $this->hasMany(WorkSchedule::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /** Alias accessor (legacy) */
    public function getBaseSalaryAttribute()
    {
        return $this->salary;
    }

    /** Pay Group payroll (Opsi A: kolom langsung). */
    public function payGroup(): BelongsTo
    {
        return $this->belongsTo(PayGroup::class);
    }

    /** (Opsional) Pivot kalau nanti pakai histori/multi-group. */
    public function payGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\PayGroup::class,
            'employee_pay_group',
            'employee_id',
            'pay_group_id'
        );
    }

    /* =======================
     |  Scopes
     |=======================*/
    public function scopeInPayGroup($query, int $payGroupId)
    {
        return $query->where('pay_group_id', $payGroupId);
    }

    /* =======================
     |  Leave helpers
     |=======================*/
    public function leaveTakenThisYear()
    {
        return $this->leaveRequests()
            ->whereYear('start_date', now()->year)
            ->where('status', 'approved')
            ->sum('total_days');
    }

    public function remainingLeaveDays()
    {
        $totalQuota = 12; // Cuti per tahun
        return $totalQuota - $this->leaveTakenThisYear();
    }

    /* =======================
     |  Payroll helpers
     |=======================*/

    /**
     * Ambil rate komponen (BASIC/OT/...) dengan prioritas:
     * 1) Employee-specific (PayComponentRate.employee_id = this)
     * 2) By Pay Group (PayComponentRate.pay_group_id = this->pay_group_id)
     * 3) Default (tanpa employee & group)
     * 4) Fallback khusus BASIC: pakai employees.salary
     */
    // public function resolveComponentRate(string $componentCode, ?Carbon $onDate = null): ?float
    // {
    //     $onDate = $onDate ?? now();

    //     // 1) Employee-specific
    //     $r = \App\Models\PayComponentRate::query()
    //         ->where('component_code', $componentCode)
    //         ->where('employee_id', $this->id)
    //         ->where(function ($q) use ($onDate) {
    //             $q->whereNull('effective_start')->orWhere('effective_start', '<=', $onDate);
    //         })
    //         ->where(function ($q) use ($onDate) {
    //             $q->whereNull('effective_end')->orWhere('effective_end', '>=', $onDate);
    //         })
    //         ->orderByDesc('effective_start')
    //         ->first();
    //     if ($r) return (float) $r->rate;

    //     // 2) By Pay Group
    //     if (!empty($this->pay_group_id)) {
    //         $r = \App\Models\PayComponentRate::query()
    //             ->where('component_code', $componentCode)
    //             ->where('pay_group_id', $this->pay_group_id)
    //             ->where(function ($q) use ($onDate) {
    //                 $q->whereNull('effective_start')->orWhere('effective_start', '<=', $onDate);
    //             })
    //             ->where(function ($q) use ($onDate) {
    //                 $q->whereNull('effective_end')->orWhere('effective_end', '>=', $onDate);
    //             })
    //             ->orderByDesc('effective_start')
    //             ->first();
    //         if ($r) return (float) $r->rate;
    //     }

    //     // 3) Default (no employee & no group)
    //     $r = \App\Models\PayComponentRate::query()
    //         ->where('component_code', $componentCode)
    //         ->whereNull('employee_id')
    //         ->whereNull('pay_group_id')
    //         ->where(function ($q) use ($onDate) {
    //             $q->whereNull('effective_start')->orWhere('effective_start', '<=', $onDate);
    //         })
    //         ->where(function ($q) use ($onDate) {
    //             $q->whereNull('effective_end')->orWhere('effective_end', '>=', $onDate);
    //         })
    //         ->orderByDesc('effective_start')
    //         ->first();
    //     if ($r) return (float) $r->rate;

    //     // 4) Fallback untuk BASIC → pakai kolom salary (legacy)
    //     if ($componentCode === 'BASIC' && !is_null($this->salary)) {
    //         return (float) $this->salary;
    //     }

    //     return null;
    // }


    public function resolveComponentRate(string $componentCode, ?\Carbon\Carbon $onDate = null): ?float
{
    $onDate = $onDate ?? now();

    // Deteksi skema pay_component_rates
    $hasTable = \Illuminate\Support\Facades\Schema::hasTable('pay_component_rates');
    if (!$hasTable) {
        return $componentCode === 'BASIC' && !is_null($this->salary) ? (float) $this->salary : null;
    }

    $colCode = null;
    if (\Illuminate\Support\Facades\Schema::hasColumn('pay_component_rates','component_code')) {
        $colCode = 'component_code';
    } elseif (\Illuminate\Support\Facades\Schema::hasColumn('pay_component_rates','code')) {
        $colCode = 'code';
    }

    // 1) Skema berbasis code di pay_component_rates
    if ($colCode) {
        $base = \App\Models\PayComponentRate::query()->where($colCode, $componentCode)
            ->where(function ($q) use ($onDate) {
                $q->whereNull('effective_start')->orWhere('effective_start', '<=', $onDate);
            })
            ->where(function ($q) use ($onDate) {
                $q->whereNull('effective_end')->orWhere('effective_end', '>=', $onDate);
            })
            ->orderByDesc('effective_start');

        // 1a) Employee-specific
        $r = (clone $base)->where('employee_id', $this->id)->first();
        if ($r) return (float) $r->rate;

        // 1b) By Pay Group
        if (!empty($this->pay_group_id)) {
            $r = (clone $base)->where('pay_group_id', $this->pay_group_id)->first();
            if ($r) return (float) $r->rate;
        }

        // 1c) Default
        $r = (clone $base)->whereNull('employee_id')->whereNull('pay_group_id')->first();
        if ($r) return (float) $r->rate;
    }

    // 2) Skema berbasis pay_component_id (join ke PayComponent)
    if (\Illuminate\Support\Facades\Schema::hasColumn('pay_component_rates','pay_component_id')
        && class_exists(\App\Models\PayComponent::class)) {

        $componentId = \App\Models\PayComponent::where(function($q) use ($componentCode){
                $q->where('code', $componentCode)->orWhere('name', $componentCode);
            })->value('id');

        if ($componentId) {
            $base = \App\Models\PayComponentRate::query()->where('pay_component_id', $componentId)
                ->where(function ($q) use ($onDate) {
                    $q->whereNull('effective_start')->orWhere('effective_start', '<=', $onDate);
                })
                ->where(function ($q) use ($onDate) {
                    $q->whereNull('effective_end')->orWhere('effective_end', '>=', $onDate);
                })
                ->orderByDesc('effective_start');

            $r = (clone $base)->where('employee_id', $this->id)->first();
            if ($r) return (float) $r->rate;

            if (!empty($this->pay_group_id)) {
                $r = (clone $base)->where('pay_group_id', $this->pay_group_id)->first();
                if ($r) return (float) $r->rate;
            }

            $r = (clone $base)->whereNull('employee_id')->whereNull('pay_group_id')->first();
            if ($r) return (float) $r->rate;
        }
    }

    // 3) Fallback BASIC → pakai kolom salary (legacy)
    if ($componentCode === 'BASIC' && !is_null($this->salary)) {
        return (float) $this->salary;
    }

    return null;
}
public function getAvatarUrlAttribute(): string
{
    // 1) Prioritas: foto employee, lalu foto user terkait, lalu fallback
    $path = $this->photo ?: ($this->user->photo ?? null);

    if ($path) {
        // Jika sudah berbentuk URL penuh atau data URI
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:image')) {
            return $path;
        }

        // Jika file disimpan di disk 'public' (storage/app/public/...)
        if (Storage::disk('public')->exists($path)) {
            return Storage::url($path);      // hasilnya /storage/xxxx.jpg
        }

        // Jika file ada di public_path langsung (misal public/uploads/xxx.jpg)
        if (file_exists(public_path($path))) {
            return asset($path);
        }
    }

    // Fallback: avatar default
    return asset('images/avatar-default.png');
}

    public function leaveEntitlements() { return $this->hasMany(\App\Models\LeaveEntitlement::class); }
    public function leaveLedgers()      { return $this->hasMany(\App\Models\LeaveLedger::class); }

}
