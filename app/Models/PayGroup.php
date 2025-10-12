<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class PayGroup extends Model
{
    use SoftDeletes;

    protected $fillable = ['code','name','active','notes'];
    protected $casts = ['active'=>'bool'];

    public function components(){
        return $this->hasMany(\App\Models\PayGroupComponent::class);
    }

    public function schedule(): BelongsTo
    {
        // Ganti \App\Models\Schedule ke nama model yang kamu pakai: Schedule / WorkSchedule / ShiftSchedule
        return $this->belongsTo(\App\Models\Schedule::class, 'schedule_id');
    }

    public function employees() { return $this->hasMany(Employee::class); }
}
