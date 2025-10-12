<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LeaveEntitlement extends Model
{
    protected $fillable = [
        'employee_id','leave_type_id','period_start','period_end',
        'opening_balance','accrued','used','adjustments',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
    ];

    // --- APPENDS supaya otomatis tampil di toArray() / JSON
    protected $appends = ['used', 'balance'];

    /** Hitung USED dari pengajuan approved yang overlap dgn periode entitlement */
    public function getUsedAttribute(): float
    {
        // Catatan: ini menghitung full $lr->days untuk setiap request yang OVERLAP periode.
        // Jika kamu mengizinkan cuti lintas tahun, pertimbangkan split saat approve
        // atau hitung ulang hanya porsi hari di dalam periode.
        return (float) LeaveRequest::query()
            ->where('employee_id', $this->employee_id)
            ->where('leave_type_id', $this->leave_type_id)
            ->where('status', 'approved')
            ->where(function ($q) {
                $q->whereBetween('start_date', [$this->period_start, $this->period_end])
                  ->orWhereBetween('end_date',   [$this->period_start, $this->period_end])
                  ->orWhere(function ($qq) {
                      $qq->where('start_date', '<=', $this->period_start)
                         ->where('end_date',   '>=', $this->period_end);
                  });
            })
            ->sum('days');
    }

    /** Sisa kuota = opening + accrued + adjustments - used(computed) */
    public function getBalanceAttribute(): float
    {
        return (float) ($this->opening_balance ?? 0)
             + (float) ($this->accrued ?? 0)
             + (float) ($this->adjustments ?? 0)
             - (float) ($this->used ?? 0);
    }

    /** Opsional: eager-load kolom computed_used via subquery (lebih irit N+1) */
    public function scopeWithComputedUsed($q)
    {
        $q->addSelect([
            'computed_used' => LeaveRequest::selectRaw('COALESCE(SUM(days),0)')
                ->whereColumn('leave_requests.employee_id', 'leave_entitlements.employee_id')
                ->whereColumn('leave_requests.leave_type_id','leave_entitlements.leave_type_id')
                ->where('status','approved')
                ->where(function ($q2) {
                    $q2->whereBetween('start_date', [DB::raw('leave_entitlements.period_start'), DB::raw('leave_entitlements.period_end')])
                       ->orWhereBetween('end_date',   [DB::raw('leave_entitlements.period_start'), DB::raw('leave_entitlements.period_end')])
                       ->orWhere(function ($qq) {
                           $qq->whereColumn('start_date','<=','leave_entitlements.period_start')
                              ->whereColumn('end_date',  '>=','leave_entitlements.period_end');
                       });
                })
        ]);
    }

    // Jika memakai scope di atas, kamu bisa override accessor agar pakai computed_used bila tersedia:
    public function getUsedAttributeOriginal(): float { return 0.0; } // dummy
    public function getUsedAttributeWrapped(): float
    {
        return isset($this->attributes['computed_used'])
            ? (float) $this->attributes['computed_used']
            : $this->getUsedAttribute();
    }

       /* Relations */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }
}
