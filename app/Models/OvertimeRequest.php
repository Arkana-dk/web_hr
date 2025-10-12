<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OvertimeRequest extends Model
{
    use HasFactory;

    protected $table = 'overtime_requests';

    protected $fillable = [
        'employee_id',
        'date',
        'start_time',
        'end_time',
        'day_type',
        'meal_option',
        'transport_route',
        'reason',
        'status',
        'approved_by',
    ];

    protected $casts = [
        'date'       => 'date',
        'start_time' => 'datetime:H:i',
        'end_time'   => 'datetime:H:i',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
