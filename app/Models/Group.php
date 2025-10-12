<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name', 'code', 'description'];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
    public function shiftRotations()
    {
    return $this->hasMany(ShiftRotation::class);
    }

    // WorkSchedule.php
    public function group()
    {
    return $this->belongsTo(Group::class);
    }

    public function shift()
    {
    return $this->belongsTo(Shift::class);
    }
}
