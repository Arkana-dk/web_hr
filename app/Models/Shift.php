<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
    ];

    /**
     * Satu Shift bisa dipakai oleh banyak ShiftGroup
     */
    public function groups()
    {
        return $this->hasMany(ShiftGroup::class, 'shift_id');
    }

    /**
     * Jadwal (Schedule) yang terkait dengan Shift ini
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
