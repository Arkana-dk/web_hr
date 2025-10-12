<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = ['code','name','is_paid','requires_attachment'];

    public function policies()     { return $this->hasMany(LeavePolicy::class); }
    public function entitlements() { return $this->hasMany(LeaveEntitlement::class); }
    public function ledgers()      { return $this->hasMany(LeaveLedger::class); }
    public function requests()     { return $this->hasMany(LeaveRequest::class); }
}
