<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShiftChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'from_shift_id',
        'to_shift_id',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $dates = ['date', 'reviewed_at'];

    // 🔁 Relasi ke Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // 🔁 Relasi ke Shift Asal
    public function fromShift()
    {
        return $this->belongsTo(Shift::class, 'from_shift_id');
    }

    // 🔁 Relasi ke Shift Tujuan
    public function toShift()
    {
        return $this->belongsTo(Shift::class, 'to_shift_id');
    }

    // 🔁 Relasi ke User Reviewer
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
