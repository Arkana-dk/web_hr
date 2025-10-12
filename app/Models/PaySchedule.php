<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaySchedule extends Model
{
    protected $fillable = ['code','name','frequency','period_start_day','period_end_day'];
}
