<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PayRun;
use Illuminate\Support\Facades\DB;

class PayRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payroll.run.view');
    }

    public function view(User $user, PayRun $run): bool
    {
        return $user->can('payroll.run.view');
    }

    public function simulate(User $user, PayRun $run): bool
    {
        // hanya boleh simulate jika punya izin & run belum terkunci
        return $user->can('payroll.run.simulate') && is_null($run->locked_at);
    }

    public function finalize(User $user, PayRun $run): bool
    {
        // butuh izin & belum locked
        if (! $user->can('payroll.run.finalize') || $run->locked_at) {
            return false;
        }

        // Maker–checker (opsional): yang finalize ≠ last simulator
        $makerId = DB::table('pay_run_audits')
            ->where('pay_run_id', $run->id)
            ->where('action', 'SIMULATE_END')
            ->latest('id')
            ->value('actor_id');

        return $makerId ? $makerId !== $user->id : true;
    }

    public function reopen(User $user, PayRun $run): bool
    {
        // hanya boleh reopen jika terkunci
        return $user->can('payroll.run.reopen') && ! is_null($run->locked_at);
    }
}
