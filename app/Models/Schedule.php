<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = ['shift_group_id', 'date', 'shift_id'];

    public function group()
    {
        return $this->belongsTo(ShiftGroup::class, 'shift_group_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
