<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveLedger extends Model
{
    protected $fillable = [
        'employee_id','leave_type_id','entry_date','entry_type','quantity','leave_request_id','note'
    ];

    protected $casts = [
        'entry_date' => 'date',
        'quantity'   => 'float',
    ];

    // kalau tabel TIDAK punya created_at/updated_at:
    // public $timestamps = false;

    // Aksesori agar view bisa pakai $ledger->direction
    public function getDirectionAttribute(): ?string
    {
        if (!empty($this->entry_type)) {
            return strtolower($this->entry_type); // 'debit' | 'credit'
        }
        if ($this->quantity === null) return null;
        return $this->quantity < 0 ? 'debit' : 'credit';
    }

    public function employee()     { return $this->belongsTo(Employee::class); }
    public function leaveType()    { return $this->belongsTo(LeaveType::class); }
    public function leaveRequest() { return $this->belongsTo(LeaveRequest::class); }
}
