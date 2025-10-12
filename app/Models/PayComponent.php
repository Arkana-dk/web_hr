<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayComponent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'kind',            // earning | deduction | statutory | info
        'calc_type',       // fixed | percent | rule
        'default_amount',
        'posting_side',    // optional (integrasi GL)
        'gl_account',
        'cost_center',
        'effective_start',
        'effective_end',
        'attributes',
        'active',
        'notes',
    ];

    protected $casts = [
        'default_amount'  => 'decimal:2',
        'effective_start' => 'date',
        'effective_end'   => 'date',
        'attributes'      => 'array',
        'active'          => 'bool',
    ];

    protected $appends = ['side'];

    public function getSideAttribute()
    {
        // 1) Kalau kamu memutuskan pakai kolom posting_side, pakai ini duluan
        if (!empty($this->attributes['posting_side'])) {
            return $this->attributes['posting_side']; // 'earning' | 'deduction'
        }

        // 2) Fallback: turunkan dari kind
        $k = $this->attributes['kind'] ?? 'earning';

        // kelompok yang harusnya POTONGAN (keluar dari gaji karyawan)
        $asDeduction = [
            'deduction','loan','tax','pph21','pph21_employee','bpjs_ee','jht_ee','jp_ee','jkk_ee','jks_ee','jpk_ee',
        ];

        return in_array(strtolower($k), $asDeduction) ? 'deduction' : 'earning';
    }

    /* ================= Relations ================= */

    public function groupMappings()
    {
        return $this->hasMany(PayGroupComponent::class)->orderBy('sequence');
    }

    public function employeeOverrides()
    {
        return $this->hasMany(EmployeeComponent::class);
    }

    public function rates()
    {
        return $this->hasMany(PayComponentRate::class)
            ->orderByDesc('effective_start')
            ->orderByDesc('id'); // tie-breaker kalau tanggal sama
    }

    /* ================= Scopes ================= */

    public function scopeActive($q, bool $active = true)
    {
        return $q->where('active', $active);
    }

    public function scopeEffectiveOn($q, $date)
    {
        return $q->where(function ($qq) use ($date) {
                $qq->whereNull('effective_start')->orWhereDate('effective_start', '<=', $date);
            })
            ->where(function ($qq) use ($date) {
                $qq->whereNull('effective_end')->orWhereDate('effective_end', '>=', $date);
            });
    }
}
