<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayRunItem extends Model
{
    protected $fillable = [
        'pay_run_id','employee_id','gross_earnings','total_deductions','net_pay',
        'result_status','diagnostics'
    ];
    protected $casts = ['diagnostics'=>'array'];

    public function details() { return $this->hasMany(PayRunDetail::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
}
