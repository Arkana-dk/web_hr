<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayGroupComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'pay_group_id',
        'pay_component_id',
        'sequence',   // urutan tampil/hitung
        'mandatory',  // komponen wajib di group ini
        'active',     // aktif/non-aktif
        'notes',
        // kalau kamu masih menyimpan kolom legacy di DB dan ingin bisa diisi:
        // 'posting_side','gl_account','cost_center',
    ];

    protected $casts = [
        'sequence'  => 'int',
        'mandatory' => 'bool',
        'active'    => 'bool',
    ];

    /* ============== Relations ============== */
    public function component()
    {
        return $this->belongsTo(PayComponent::class, 'pay_component_id');
    }

    public function payGroup()
    {
        return $this->belongsTo(PayGroup::class);
    }

    /* ============== Scopes ============== */
    public function scopeActive($q, bool $active = true)
    {
        return $q->where('active', $active);
    }

    public function scopeOrdered($q)
    {
        return $q->orderBy('sequence');
    }
}
