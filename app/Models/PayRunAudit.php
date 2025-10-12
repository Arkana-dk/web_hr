<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayRunAudit extends Model
{
    protected $table = 'pay_run_audits';

    protected $fillable = [
        'pay_run_id', 'actor_id', 'action', 'before_json', 'after_json'
    ];

    protected $casts = [
        'before_json' => 'array',
        'after_json'  => 'array',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function payRun()
    {
        return $this->belongsTo(PayRun::class, 'pay_run_id');
    }

    public function actor()
    {
        return $this->belongsTo(\App\Models\User::class, 'actor_id');
    }
}
