<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'date',
        'type',
        'reason',
        'status',
        'submission_time',
        'reviewed_by',
    ];

    public $timestamps = true;

    protected $casts = [
        'submission_time' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

