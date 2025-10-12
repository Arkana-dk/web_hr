<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayRun;
use App\Models\PayRunAudit;
use App\Models\User;
use Illuminate\Http\Request;

class PayRunAuditController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:payroll.rate.manage'])->only(['index']);
    }

    public function index(Request $r, PayRun $payrun = null)
{
    $action   = $r->query('action');
    $payRunId = $r->query('pay_run_id') ?? optional($payrun)->id;
    $actorId  = $r->query('actor_id'); // nanti tidak dipakai di Blade
    $dateFrom = $r->query('date_from');
    $dateTo   = $r->query('date_to');

    $audits = PayRunAudit::with(['actor', 'payRun'])
        ->when($payRunId, fn($q) => $q->where('pay_run_id', $payRunId))
        ->when(in_array($action, ['FINALIZE', 'REOPEN']), fn($q) => $q->where('action', $action))
        ->when($actorId, fn($q) => $q->where('actor_id', $actorId))
        ->when($dateFrom, fn($q) => $q->whereDate('created_at', '>=', $dateFrom))
        ->when($dateTo, fn($q) => $q->whereDate('created_at', '<=', $dateTo))
        ->orderByDesc('id')
        ->paginate(20)
        ->withQueryString();

    $audits->getCollection()->transform(function ($a) {
        $after = (array) ($a->after_json ?? []);
        $a->summary = [
            'total'  => $after['total']  ?? null,
            'count'  => $after['count']  ?? null,
            'group'  => $after['group']  ?? null,
        ];
        return $a;
    });

    $payruns = PayRun::select('id')->latest()->limit(50)->get();

    return view('admin.pages.payruns-audit.index', compact(
        'payrun', 'audits', 'action', 'payruns', 'payRunId', 'dateFrom', 'dateTo'
    ));
}

}
