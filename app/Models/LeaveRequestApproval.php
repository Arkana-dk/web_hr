<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequestApproval extends Model
{
    protected $fillable = ['leave_request_id','sequence','role','approver_id','status','acted_at'];
    protected $casts = ['acted_at'=>'datetime'];

    public function request()   { return $this->belongsTo(LeaveRequest::class, 'leave_request_id'); }
    public function approver()  { return $this->belongsTo(User::class, 'approver_id'); }
}
