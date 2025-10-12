<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingOvertime extends Model
{
    // Mass-assignment agar bisa pakai ::create()
    protected $fillable = [
        'rate_per_hour',
        'paid_in_month',
    ];
}
