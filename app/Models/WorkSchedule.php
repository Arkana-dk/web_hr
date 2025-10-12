<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    protected $fillable = [
        'employee_id',
        'work_date',
        'shift_id'
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
    public function Employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
