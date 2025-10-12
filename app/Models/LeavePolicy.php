<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeavePolicy extends Model
{
    protected $fillable = [
        'name',
        'leave_type_id',
        'effective_start',
        'effective_end',
        'rules',
    ];

    protected $casts = [
        'effective_start' => 'date',
        'effective_end'   => 'date',
        'rules'           => 'array',
    ];

    public function leaveType() { return $this->belongsTo(LeaveType::class); }
    public function company()   { return $this->belongsTo(Company::class); }
    public function payGroup()  { return $this->belongsTo(PayGroup::class); }
}
