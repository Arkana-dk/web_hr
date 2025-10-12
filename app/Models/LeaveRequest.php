<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveEntitlement;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $table = 'leave_requests';

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days',
        'status',
        'reason',
        'attachment_path',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'approved_at' => 'datetime',
        'days'        => 'float',
    ];

    /* ===================== Relasi ===================== */
    public function employee()   { return $this->belongsTo(Employee::class); }
    public function reviewer()   { return $this->belongsTo(User::class, 'approved_by'); }
    public function approver()   { return $this->belongsTo(User::class, 'approved_by'); } // alias
    public function type()       { return $this->belongsTo(LeaveType::class, 'leave_type_id'); }
    public function approvals()  { return $this->hasMany(LeaveRequestApproval::class); }

    /* ===================== Helper Modern ===================== */
    public static function usedDaysThisYear(int $employeeId, ?int $leaveTypeId = null): float
    {
        $q = static::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereYear('start_date', now()->year);

        if ($leaveTypeId) {
            $q->where('leave_type_id', $leaveTypeId);
        }

        return (float) $q->get()->sum(fn ($lr) =>
            $lr->days ?? $lr->start_date->diffInDays($lr->end_date) + 1
        );
    }

    /* ===================== Helper Kompatibilitas Lama ===================== */
    public static function usedLeaveDaysThisYear(int $userId, string $leaveTypeName): float
    {
        $user = User::with('employee')->findOrFail($userId);
        $emp  = $user->employee;
        if (!$emp) return 0.0;

        $typeId = LeaveType::where('name', $leaveTypeName)->value('id');
        if (!$typeId) return 0.0;

        return static::usedDaysThisYear($emp->id, $typeId);
    }

    public static function remainingLeaveQuota(int $userId, string $leaveTypeName): float
    {
        $user = User::with('employee')->findOrFail($userId);
        $emp  = $user->employee;
        if (!$emp) return 0.0;

        $typeId = LeaveType::where('name', $leaveTypeName)->value('id');
        if (!$typeId) return 0.0;

        $today = Carbon::today();
        $ent = LeaveEntitlement::where('employee_id', $emp->id)
            ->where('leave_type_id', $typeId)
            ->whereDate('period_start', '<=', $today)
            ->whereDate('period_end',   '>=', $today)
            ->first();

        // langsung pakai computed balance
        return max(0.0, (float) ($ent?->balance ?? 0.0));
    }

}
