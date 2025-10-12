<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLocationSetting extends Model
{
     protected $fillable = [
        'location_name',
        'latitude',
        'longitude',
        'radius',
    ];
}
