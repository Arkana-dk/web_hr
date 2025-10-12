<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftRotation extends Model
{
    use HasFactory;

    protected $table = 'shift_rotations';

    protected $fillable = [
        'group_id',
        'order',
        'shift_id',
    ];

    /**
     * Grup kerja yang memiliki rotasi ini.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Shift yang berlaku pada rotasi ini.
     * Bisa bernilai null jika minggu libur.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
