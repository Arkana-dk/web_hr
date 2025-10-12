<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'pay_component_id',
        'override_amount',   // nominal tetap (opsional)
        'override_rate',     // tarif (opsional)
        'override_percent',  // 0.1234 = 12.34% (opsional)
        'override_formula',  // formula khusus (opsional)
        'effective_start',
        'effective_end'
        ,'active',
    ];

    protected $casts = [
        'override_amount'  => 'decimal:2',
        'override_rate'    => 'decimal:4',
        'override_percent' => 'decimal:4',
        'effective_start'  => 'date',
        'effective_end'    => 'date',
        'active'           => 'bool',
    ];

    /* ============== Relations ============== */
    public function component()
    {
        return $this->belongsTo(PayComponent::class, 'pay_component_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /* ============== Scopes ============== */
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
