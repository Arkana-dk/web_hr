<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayRunDetail extends Model
{
    protected $fillable = [
        'pay_run_item_id','pay_component_id','component_code','component_type',
        'name','quantity','rate','amount','source','side'
    ];
    protected $casts = ['source'=>'array'];

    public function item() { return $this->belongsTo(PayRunItem::class, 'pay_run_item_id'); }
    public function component() { return $this->belongsTo(PayComponent::class, 'pay_component_id'); }
}
