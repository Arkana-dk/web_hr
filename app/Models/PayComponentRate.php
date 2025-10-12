<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PayComponentRate extends Model
{
    protected $fillable = [
        'pay_component_id',
        'pay_group_id',
        'unit',
        'rate',
        'formula',
        'effective_start',
        'effective_end',
        'meta',              // <-- penting: agar JSON meta (basis, cap, dll) tersimpan
    ];

    protected $casts = [
        'effective_start' => 'date',
        'effective_end'   => 'date',
        'meta'            => 'array',     // <-- penting: meta jadi array otomatis
        'rate'            => 'decimal:6', // presisi 4 sesuai input step=0.0001
    ];

    // default kosong agar tidak null
    protected $attributes = [
        'meta' => '{}',
    ];

    /* ============ Relationships ============ */
    public function component() { return $this->belongsTo(PayComponent::class, 'pay_component_id'); }
    public function payGroup()  { return $this->belongsTo(PayGroup::class); }

    /* ============ Scopes berguna ============ */

    // Rate yang aktif di tanggal tertentu (default: hari ini)
    public function scopeEffectiveOn(Builder $q, $date = null): Builder
    {
        $d = $date ? \Illuminate\Support\Carbon::parse($date)->toDateString() : now()->toDateString();
        return $q->whereDate('effective_start', '<=', $d)
                 ->where(function ($qq) use ($d) {
                     $qq->whereNull('effective_end')->orWhereDate('effective_end', '>=', $d);
                 });
    }

    // Filter by component & (opsional) pay_group
    public function scopeFor(Builder $q, int $componentId, ?int $payGroupId = null): Builder
    {
        return $q->where('pay_component_id', $componentId)
                 ->when(!is_null($payGroupId),
                     fn($qq) => $qq->where('pay_group_id', $payGroupId),
                     fn($qq) => $qq->whereNull('pay_group_id'));
    }

    /* ============ Helper: deteksi tipe dari unit ============ */

    // $rate->unit_type → 'percent' | 'daily' | 'hourly' | 'fixed' | 'other'
    public function getUnitTypeAttribute(): string
    {
        $u = strtolower((string) $this->unit);
        if (str_contains($u, '%'))       return 'percent';
        if (str_contains($u, '/day'))    return 'daily';
        if (str_contains($u, '/hour'))   return 'hourly';
        if ($u === 'idr' || Str::startsWith($u, 'idr')) return 'fixed';
        return 'other';
    }
}
