<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftGroup extends Model
{
    protected $fillable = [
        'name',
        'shift_id',
    ];

    /**
     * Satu ShiftGroup punya satu Shift
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Pivot table shift_group_employee
     */
    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'shift_group_employee')
                    ->withTimestamps();
    }

    /**
     * Jadwal (Schedule) bila ada
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
