<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayRun extends Model
{
    protected $fillable = [
        'pay_group_id','start_date','end_date','status','note',
        'created_by','approved_by','approved_at','finalized_at','locked_at', // + finalized_at
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'approved_at'  => 'datetime',
        'finalized_at' => 'datetime', // + finalized_at
        'locked_at'    => 'datetime',
    ];

    // Helpers
    public function isFinalized(): bool { return !is_null($this->finalized_at); }
    public function isLocked(): bool { return !is_null($this->locked_at); }

    public function items()    { return $this->hasMany(PayRunItem::class); }
    public function payGroup() { return $this->belongsTo(PayGroup::class); }
    // relasi user yang mengunci/finalize
    public function lockedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'locked_by');
    }

    // (opsional) relasi user pembuat
    public function createdByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
    public function audits()
{
    return $this->hasMany(\App\Models\PayRunAudit::class);
}

}
