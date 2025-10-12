<?php

namespace App\Domain\LeaveRequest\Services;

use App\Domain\LeaveRequest\Contracts\{
    LeaveRequestServiceContract,
    LeavePolicyResolverContract,
    WorkingDaysCalculatorContract,
    LeaveBalanceServiceContract
};
use App\Domain\LeaveRequest\Rules\LeaveRulesEvaluator;
use App\Models\{Employee, LeaveRequest, LeaveLedger};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class LeaveRequestService implements LeaveRequestServiceContract
{
    public function __construct(
        protected LeavePolicyResolverContract   $resolver,
        protected WorkingDaysCalculatorContract $workingDays,
        protected LeaveRulesEvaluator           $rules,
        protected LeaveBalanceServiceContract   $balance,
    ) {}

    public function create(
        Employee $emp,
        int $leaveTypeId,
        Carbon $start,
        Carbon $end,
        ?string $reason = null,
        ?string $attachmentPath = null
    ): LeaveRequest {
        // 1) Ambil policy yang berlaku
        $policy = $this->resolver->resolve($emp, $leaveTypeId, Carbon::today());
        if (!$policy) {
            throw new \RuntimeException('Policy cuti yang berlaku tidak ditemukan.');
        }
        $rules = $policy->rules ?? [];

        // 2) Hitung hari kerja
        $days = $this->workingDays->count($emp, $start, $end, (bool)($rules['exclude_weekends'] ?? true));

        // 3) Validasi aturan
        $this->rules->validate($rules, $start, $end, [
            'employee_gender' => $emp->gender ?? null,
            'days'            => $days,
        ]);

        // 4) Anti-overlap (pending/approved)
        $hasOverlap = LeaveRequest::where('employee_id', $emp->id)
            ->whereIn('status', ['pending','approved'])
            ->where(function($q) use ($start,$end){
                $q->whereBetween('start_date', [$start,$end])
                  ->orWhereBetween('end_date', [$start,$end])
                  ->orWhere(function($qq) use ($start,$end){
                      $qq->where('start_date','<=',$start)->where('end_date','>=',$end);
                  });
            })->exists();
        if ($hasOverlap) {
            throw new \RuntimeException('Rentang tanggal bertabrakan dengan pengajuan lain.');
        }

        // 5) Cek kuota
        $ent = $this->balance->getPeriodEntitlement($emp, $leaveTypeId, $start);
        if (!$ent) {
            throw new \RuntimeException('Mungkin periode cuti belum dibuat, atau kuota belum ditentukan. Mohon hubungi HR');
        }
        $saldo = $this->balance->computeBalance($ent);

        $allowNeg = (bool)($rules['allow_negative_balance'] ?? false);
        if (!$allowNeg && $days > $saldo) {
            throw new \RuntimeException('Saldo cuti tidak mencukupi.');
        }

        // 6) (Opsional) Batas per-bulan
        $maxPerMonth = $rules['max_days_per_month'] ?? null;
        if ($maxPerMonth) {
            $monthStart = (clone $start)->startOfMonth()->toDateString();
            $monthEnd   = (clone $start)->endOfMonth()->toDateString();

            $usedThisMonth = LeaveRequest::where('employee_id', $emp->id)
                ->where('leave_type_id', $leaveTypeId)
                ->where('status','approved')
                ->whereBetween('start_date', [$monthStart,$monthEnd])
                ->sum('days');

            if (($usedThisMonth + $days) > $maxPerMonth) {
                throw new \RuntimeException("Batas pemakaian bulan ini terlewati (maks {$maxPerMonth} hari).");
            }
        }

        // 7) Simpan pengajuan
        return DB::transaction(function() use ($emp,$leaveTypeId,$start,$end,$days,$reason,$attachmentPath) {
            /** @var LeaveRequest $lr */
            $lr = LeaveRequest::create([
                'employee_id'     => $emp->id,
                'leave_type_id'   => $leaveTypeId,
                'start_date'      => $start->toDateString(),
                'end_date'        => $end->toDateString(),
                'days'            => $days,
                'status'          => 'pending',
                'reason'          => $reason,
                'attachment_path' => $attachmentPath,
            ]);

            // TODO: generate approval steps sesuai policy['approval_flow'] jika multi-level

            return $lr;
        });
    }

    public function approve(LeaveRequest $lr, int $approverId): void
    {
        DB::transaction(function () use ($lr, $approverId) {
            $lr->update([
                'status'      => 'approved',
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            // Catat ledger USE (konvensi: positif → kita hitung sebagai 'used' di entitlement)
            LeaveLedger::create([
                'employee_id'      => $lr->employee_id,
                'leave_type_id'    => $lr->leave_type_id,
                'entry_date'       => now()->toDateString(),
                'entry_type'       => 'USE',
                'quantity'         => $lr->days,
                'leave_request_id' => $lr->id,
                'note'             => 'Leave approved',
            ]);

            // TODO: sinkron ke Attendance jika perlu
        });
    }

    public function reject(LeaveRequest $lr): void
    {
        $lr->update(['status' => 'rejected']);
    }
}
