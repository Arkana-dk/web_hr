<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{PayRun, PayRunItem, PayRunDetail, PayGroup, Employee, PayComponent};
use App\Domain\Payroll\Services\{DefaultPayrollCalculator, PayContextFactory, PreFinalizeChecker};
use App\Domain\Payroll\Rules\{ProrataBasicRule, OvertimeRule, DailyAllowanceRule, StatutoryContributionRule, GenericComponentRule, LatePenaltyRule};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use App\Exports\PayRunExport;
use Maatwebsite\Excel\Facades\Excel;          // <-- penting, Facade Excel
use Maatwebsite\Excel\Excel as ExcelWriter;   // <-- untuk konstanta CSV/XLSX

class PayRunWizardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','permission:payroll.run.view'])->only(['index','review']);
        $this->middleware(['auth','permission:payroll.run.simulate'])->only(['simulate']);
        $this->middleware(['auth','permission:payroll.run.finalize'])->only(['finalize']);
        $this->middleware(['auth','permission:payroll.run.reopen'])->only(['reopen']);
    }

    /**
     * List pay runs + filter
     */
    public function index(Request $r)
    {
        $q = PayRun::with(['payGroup','lockedByUser','createdByUser'])->latest('start_date');

        if ($r->filled('status')) {
            $q->where('status', $r->status);
        }
        if ($r->filled('group')) {
            $q->where('pay_group_id', $r->group);
        }
        if ($r->filled('q')) {
            $text = '%'.$r->q.'%';
            $q->whereHas('payGroup', function ($g) use ($text) {
                $g->where('name', 'like', $text)->orWhere('code', 'like', $text);
            });
        }

        $runs   = $q->paginate(20)->withQueryString();
        $groups = PayGroup::orderBy('name')->get();

        return view('admin.pages.payruns.index', compact('runs','groups'));
    }

    /**
     * Form create pay run
     */
    public function create()
    {
        $groups = PayGroup::orderBy('name')->get();
        return view('admin.pages.payruns.create', compact('groups'));
    }

    /**
     * Store draft pay run (cek overlap)
     */
    public function store(Request $r)
    {
        $r->validate([
            'pay_group_id' => 'required|exists:pay_groups,id',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after_or_equal:start_date',
        ]);

        $existsExact = PayRun::where('pay_group_id', $r->pay_group_id)
            ->whereDate('start_date', $r->start_date)
            ->whereDate('end_date', $r->end_date)
            ->first();

        if ($existsExact) {
            return redirect()->route('admin.payruns.review', $existsExact)
                ->with('info', 'Pay run untuk periode ini sudah ada. Dialihkan ke Review.');
        }

        $overlap = PayRun::where('pay_group_id', $r->pay_group_id)
            ->where(function ($q) use ($r) {
                $q->whereDate('start_date', '<=', $r->end_date)
                  ->whereDate('end_date', '>=', $r->start_date);
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_date' => 'Ada pay run lain yang overlap pada pay group ini. Periksa periode.',
            ]);
        }

        $run = PayRun::create([
            'pay_group_id' => $r->pay_group_id,
            'start_date'   => $r->start_date,
            'end_date'     => $r->end_date,
            'status'       => 'draft',
            'created_by'   => optional($r->user())->id,
        ]);

        return redirect()->route('admin.payruns.review', $run)
            ->with('success', 'Draft pay run berhasil dibuat.');
    }

    /**
     * Halaman review (ringkasan & daftar item)
     */
   public function review(PayRun $payrun)
    {
        $factory = app(\App\Domain\Payroll\Services\PayContextFactory::class);
        // Siapa yang finalize/lock?
        $payrun->loadMissing('lockedByUser');

        $finalizedBy = optional($payrun->lockedByUser)->name;
        if (!$finalizedBy && \Illuminate\Support\Facades\Schema::hasTable('pay_run_audits')) {
            $finalizedBy = \Illuminate\Support\Facades\DB::table('pay_run_audits as a')
                ->leftJoin('users as u', 'u.id', '=', 'a.actor_id')
                ->where('a.pay_run_id', $payrun->id)
                ->where('a.action', 'FINALIZE')
                ->orderByDesc('a.id')
                ->value('u.name');
        }


        // --- ambil parameter filter
        $q      = trim((string) request('q', ''));
        $status = request('status');
        $allowedStatus = ['ok','warning','error'];
        if (!in_array($status, $allowedStatus, true)) {
            $status = null; // sanitasi nilai asing
        }

        // --- build query + filter
        $items = PayRunItem::with('employee')
            ->where('pay_run_id', $payrun->id)
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->whereHas('employee', fn($e) => $e->where('name', 'like', $like));
            })
            ->when($status, fn($query) => $query->where('result_status', $status))
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString(); // penting agar filter tidak hilang saat ganti halaman

        // --- hitung data tambahan per item (daily rate, paid/scheduled)
        $items->getCollection()->transform(function ($item) use ($factory, $payrun) {
            $ctx = $item->context
                ?? (method_exists($factory, 'makeContext') ? $factory->makeContext($payrun, $item->employee) : null);

            $item->daily_basic_rate = $ctx ? $factory->computeDailyBasicRate($ctx) : null;
            $item->scheduled_days   = data_get($ctx, 'scheduled_days');
            $item->paid_days        = data_get($ctx, 'paid_days');

            return $item;
        });

        // (opsional) total exception seluruh payrun (tidak terpengaruh filter tampilan)
        $exceptions = PayRunItem::where('pay_run_id', $payrun->id)
            ->where('result_status', '!=', 'ok')
            ->count();

        $totalEmployees = $this->employeesInGroupQuery($payrun->pay_group_id)->count();

        return view('admin.pages.payruns.review', compact('payrun', 'items', 'exceptions', 'totalEmployees'));
    }

    public function showItem(PayRun $payrun, PayRunItem $item)
{
    abort_if($item->pay_run_id !== $payrun->id, 404);
    $item->load('employee');

    // === Tambahkan ini ===
    $payrun->loadMissing('lockedByUser');
    $finalizedBy = optional($payrun->lockedByUser)->name;

    if (!$finalizedBy && Schema::hasTable('pay_run_audits')) {
        $finalizedBy = DB::table('pay_run_audits as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.actor_id')
            ->where('a.pay_run_id', $payrun->id)
            ->where('a.action', 'FINALIZE')
            ->orderByDesc('a.id')
            ->value('u.name');
    }

    // Base query
    $q = PayRunDetail::query()
        ->with('component') // pastikan relasi component ada
        ->where('pay_run_item_id', $item->id);

    // === Prefer DB-level sort kalau kolom tersedia ===
    $didDbSort = false;

    // 1) Kalau kolom side ada di pay_run_details → urutkan langsung
    if (Schema::hasColumn('pay_run_details', 'side')) {
        $q->orderBy('side');
        $didDbSort = true;
    }
    // 2) Kalau tidak ada, coba urutkan via JOIN ke pay_components.side
    elseif (Schema::hasTable('pay_components') && Schema::hasColumn('pay_components', 'side')) {
        $q->leftJoin('pay_components', 'pay_components.id', '=', 'pay_run_details.component_id')
          ->orderBy('pay_components.side')
          ->select('pay_run_details.*'); // penting: hydrate tetap ke model PayRunDetail
        $didDbSort = true;
    }

    // 3) Tambahkan sort_order kalau kolomnya ada
    if (Schema::hasColumn('pay_run_details', 'sort_order')) {
        $q->orderBy('sort_order');
        $didDbSort = true;
    }

    // Ambil data
    $details = $q->get()->map(function ($d) {
        // Normalisasi meta agar field rate terbaca walau tidak ada kolom dedicated
        $meta = is_array($d->meta ?? null) ? $d->meta : (json_decode($d->meta ?? '[]', true) ?: []);

        // Isi side dari beberapa sumber: kolom -> meta -> relasi component
        $d->side           = $d->side           ?? ($meta['side'] ?? data_get($d, 'component.side'));
        $d->rate_type      = $d->rate_type      ?? ($meta['rate_type']  ?? null);
        $d->rate_value     = $d->rate_value     ?? ($meta['rate_value'] ?? null);
        $d->unit           = $d->unit           ?? ($meta['unit']       ?? null);
        $d->source         = $d->source         ?? ($meta['source']     ?? null);
        $d->basis          = $d->basis          ?? ($meta['basis']      ?? null);
        $d->quantity       = $d->quantity       ?? ($meta['qty']        ?? null);
        $d->sort_order     = $d->sort_order     ?? ($meta['sort_order'] ?? null);
        $d->component_code = $d->component_code ?? data_get($d, 'component.code');
        $d->component_name = $d->component_name ?? data_get($d, 'component.name');

        return $d;
    });

    // === Jika tidak bisa DB-level sort, lakukan sorting di memory ===
    if (!$didDbSort) {
        $details = $details->sortBy(function ($d) {
            // earning dulu baru deduction
            $rankSide = (strtolower((string)($d->side ?? 'earning')) === 'earning') ? 0 : 1;
            $order    = is_numeric($d->sort_order) ? (int)$d->sort_order : 9999;
            $name     = (string)($d->component_name ?? '');
            // kunci gabungan untuk sort stabil
            return sprintf('%d|%04d|%s', $rankSide, $order, $name);
        })->values();
    }

    // Ringkasan nominal berdasarkan side yang sudah dinormalisasi
    $sumEarn = $details->filter(fn($d) => strtolower((string)($d->side ?? 'earning')) === 'earning')
                       ->sum('amount');
    $sumDed  = $details->filter(fn($d) => strtolower((string)($d->side ?? 'earning')) === 'deduction')
                       ->sum('amount');

    return view('admin.pages.payruns.item-show', [
        'payrun'   => $payrun,
        'item'     => $item,
        'details'  => $details,
        'sumEarn'  => $sumEarn,
        'sumDed'   => $sumDed,
        'finalizedBy'    => $finalizedBy
    ]);
}


    /**
     * Simulate pay run (idempotent) — generate PayRunItem & PayRunDetail
     */
    public function simulate(PayRun $payrun)
    {
        // ====== POLICY & LOCK GUARD ======
        $this->authorize('simulate', $payrun);
        if ($payrun->locked_at || $payrun->status === 'finalized') {
            \Log::warning('SIM_ABORT', ['reason' => 'locked_or_finalized', 'run' => $payrun->id]);
            return back()->with('error', 'Pay run terkunci/terfinalisasi. Tidak bisa disimulasikan ulang.');
        }

        // ====== META LOG: SKEMA ======
        \Log::info('SIM_START', [
            'run'    => $payrun->id,
            'group'  => $payrun->pay_group_id,
            'status' => $payrun->status,
            'period' => [$payrun->start_date, $payrun->end_date],
        ]);

        \Log::info('SIM_SCHEMA', [
            'employees.pay_group_id' => Schema::hasColumn('employees', 'pay_group_id'),
            'employee_pay_group'     => Schema::hasTable('employee_pay_group'),
            'pay_group_employee'     => Schema::hasTable('pay_group_employee'),
        ]);

        // ====== BASE EMPLOYEE QUERY ======
        $base   = $this->employeesInGroupQuery($payrun->pay_group_id);
        $count  = (clone $base)->count();
        $sample = (clone $base)->limit(5)->pluck('id');
        \Log::info('SIM_EMP', ['count' => $count, 'sample' => $sample]);

        if ($count === 0) {
            \Log::warning('SIM_ABORT', ['reason' => 'no_employees_in_group', 'run' => $payrun->id]);
            return back()->with('error', 'Tidak ada karyawan di Pay Group ini.');
        }

        // ====== CONTEXT & RULES ======
        $ctx = \App\Domain\Payroll\Services\PayContextFactory::makeFromPayRun($payrun);
        \Log::info('SIM_CTX_READY');

       $calculator = new DefaultPayrollCalculator([
                    new ProrataBasicRule(),
                    new OvertimeRule(),
                    new DailyAllowanceRule(),
                    new StatutoryContributionRule(),
                    new LatePenaltyRule(),
                    new GenericComponentRule(), // <— terakhir
         ]);

        \Log::info('SIM_RULES_READY');

        // ====== PURGE OLD (IDEMPOTENT) ======
        DB::transaction(function () use ($payrun) {
            $itemIds = \App\Models\PayRunItem::where('pay_run_id', $payrun->id)->pluck('id');
            $detailHasPayRunId = Schema::hasColumn('pay_run_details', 'pay_run_id');

            \Log::info('SIM_PURGE_BEGIN', [
                'existing_items' => $itemIds->count(),
                'detail_has_pay_run_id' => $detailHasPayRunId,
            ]);

            if ($itemIds->isNotEmpty()) {
                \App\Models\PayRunDetail::whereIn('pay_run_item_id', $itemIds)->delete();
                \App\Models\PayRunItem::whereIn('id', $itemIds)->delete();
            }
            if ($detailHasPayRunId) {
                \App\Models\PayRunDetail::where('pay_run_id', $payrun->id)->delete();
            }

            \Log::info('SIM_PURGE_DONE');
        });

        // ====== COMPONENT MAP & DETAIL COLUMN CAPABILITY ======
        $componentMap = \App\Models\PayComponent::pluck('id', 'code'); // code => id
        $detailHasPayRunId = Schema::hasColumn('pay_run_details', 'pay_run_id');
        $detailHasMeta     = Schema::hasColumn('pay_run_details', 'meta');
        $detailHasSide     = Schema::hasColumn('pay_run_details', 'side');
        $detailHasUnit     = Schema::hasColumn('pay_run_details', 'unit');
        $detailHasRateType = Schema::hasColumn('pay_run_details', 'rate_type');

        \Log::info('SIM_COMPONENT_MAP', [
            'count' => $componentMap->count(),
            'detail_cols' => [
                'pay_run_id' => $detailHasPayRunId,
                'meta'       => $detailHasMeta,
                'side'       => $detailHasSide,
                'unit'       => $detailHasUnit,
                'rate_type'  => $detailHasRateType,
            ],
        ]);

        // ====== CHUNK STRATEGY ======
        $employeePkType = null;
        try { $employeePkType = Schema::getColumnType('employees', 'id'); } catch (\Throwable $e) {}
        $useChunkById = !in_array($employeePkType, ['string','char','uuid','guid','binary', null], true);
        \Log::info('SIM_CHUNK_STRATEGY', ['pk_type' => $employeePkType, 'useChunkById' => $useChunkById]);

        // ====== MAIN LOOP ======
        $processed = 0;
        $chunkSize = 200;
        $q = (clone $base)->orderBy('id');

        // opsi: skip baris amount 0 agar tidak polusi tampilan
        $SKIP_ZERO_LINES = true;

        $chunkCallback = function ($employees) use (
            &$processed, $calculator, $ctx, $payrun, $componentMap,
            $detailHasPayRunId, $detailHasMeta, $detailHasSide, $detailHasUnit, $detailHasRateType,
            $SKIP_ZERO_LINES
        ) {
            DB::transaction(function () use (
                $employees, &$processed, $calculator, $ctx, $payrun, $componentMap,
                $detailHasPayRunId, $detailHasMeta, $detailHasSide, $detailHasUnit, $detailHasRateType,
                $SKIP_ZERO_LINES
            ) {
                foreach ($employees as $emp) {
                    $processed++;

                    // ---- COMPUTE RULES PER EMPLOYEE ----
                    try {
                        $res = $calculator->compute($ctx, $emp);
                    } catch (\Throwable $e) {
                        \Log::error('SIM_RULE_ERR', ['emp' => $emp->id, 'msg' => $e->getMessage()]);
                        $res = new \App\Domain\Payroll\DTOs\CalcResult();
                        $res->warn('Rule error: '.$e->getMessage());
                    }

                    $diagnostics = $res->diagnostics ?? null;
                    $diagJson = is_array($diagnostics)
                        ? json_encode($diagnostics)
                        : (is_string($diagnostics) ? $diagnostics : null);

                    $item = \App\Models\PayRunItem::create([
                        'pay_run_id'       => $payrun->id,
                        'employee_id'      => $emp->id,
                        'gross_earnings'   => (float) ($res->gross ?? 0),
                        'total_deductions' => (float) ($res->deductions ?? 0),
                        'net_pay'          => (float) ($res->net ?? 0),
                        'result_status'    => empty($diagnostics) ? 'ok' : 'warning',
                        'diagnostics'      => $diagJson,
                    ]);

                    // ---- BUILD DETAIL ROWS ----
                    $detailRows = [];
                    if (!empty($res->lines)) {
                        foreach ($res->lines as $line) {
                            $isArr = is_array($line);
                            $get   = function ($key) use ($isArr, $line) {
                                return $isArr ? ($line[$key] ?? null) : ($line->$key ?? null);
                            };

                            // Kode komponen: dukung camel & snake
                            $code = $get('componentCode') ?? $get('component_code');
                            // Normalisasi sumber
                            $rawSource  = $get('source') ?? 'calc';
                            $sourceJson = $this->normalizeSourceJson($rawSource);

                            // Nilai numerik
                            $qty   = is_numeric($get('quantity')) ? (float) $get('quantity') : 1.0;
                            $rate  = is_numeric($get('rate'))     ? (float) $get('rate')     : null;
                            $amt   = is_numeric($get('amount'))   ? (float) $get('amount')   : 0.0;

                            if ($SKIP_ZERO_LINES && $amt == 0.0) {
                                // Lewati baris amount 0 (opsional)
                                continue;
                            }

                            // Meta & side
                            $metaArr = $get('meta') ?? [];
                            $sideVal = $get('side') ?? null;

                            // Auto-infer side kalau belum ada: amount<0 → deduction, else earning
                            if (!$sideVal) {
                                $sideVal = ($amt < 0) ? 'deduction' : 'earning';
                            }

                            // Unit & rate_type (kalau disediakan oleh rule)
                            $unit     = $get('unit');
                            $rateType = $get('rate_type');

                            // Map component id dari code
                            $componentId = $code ? $componentMap->get($code) : null;
                            if ($code && !$componentId) {
                                \Log::warning('SIM_DETAIL_UNKNOWN_COMPONENT', [
                                    'code' => $code, 'emp' => $emp->id, 'item' => $item->id
                                ]);
                            }

                            $row = [
                                'pay_run_item_id'  => $item->id,
                                'pay_component_id' => $componentId,
                                'component_code'   => $code,
                                'component_type'   => $get('componentType') ?? $get('component_type'),
                                'name'             => $get('name'),
                                'quantity'         => $qty,
                                'rate'             => $rate,
                                'amount'           => $amt,
                                'source'           => $sourceJson,
                                'created_at'       => now(),
                                'updated_at'       => now(),
                            ];

                            if ($detailHasPayRunId)  $row['pay_run_id'] = $payrun->id;
                            if ($detailHasMeta)      $row['meta']       = json_encode($metaArr, JSON_UNESCAPED_UNICODE);
                            if ($detailHasSide)      $row['side']       = $sideVal;
                            if ($detailHasUnit && $unit !== null)           $row['unit']      = (string) $unit;
                            if ($detailHasRateType && $rateType !== null)   $row['rate_type'] = (string) $rateType;

                            $detailRows[] = $row;
                        }
                    }

                    if (!empty($detailRows)) {
                        \App\Models\PayRunDetail::insert($detailRows);
                    }

                    \Log::debug('SIM_EMP_DONE', [
                        'emp'   => $emp->id,
                        'gross' => (float) ($res->gross ?? 0),
                        'ded'   => (float) ($res->deductions ?? 0),
                        'net'   => (float) ($res->net ?? 0),
                        'lines' => count($detailRows),
                        'diag'  => $diagnostics ? (is_array($diagnostics) ? count($diagnostics) : 1) : 0,
                    ]);
                }
            });

            \Log::info('SIM_CHUNK_DONE', ['processed_so_far' => $processed]);
        };

        $useChunkById ? $q->chunkById($chunkSize, $chunkCallback)
                    : $q->chunk($chunkSize, $chunkCallback);

        // ====== SUMMARY ======
        $totals = \App\Models\PayRunItem::where('pay_run_id', $payrun->id)
            ->selectRaw('COALESCE(SUM(gross_earnings),0) AS gross, COALESCE(SUM(total_deductions),0) AS ded, COALESCE(SUM(net_pay),0) AS net')
            ->first();

        \Log::info('SIM_TOTALS', [
            'processed' => $processed,
            'gross'     => (float) ($totals->gross ?? 0),
            'ded'       => (float) ($totals->ded ?? 0),
            'net'       => (float) ($totals->net ?? 0),
        ]);

        $payrun->update(['status' => 'simulated']);
        \Log::info('SIM_DONE', ['run' => $payrun->id, 'status' => 'simulated']);

        return back()->with('success', 'Simulasi selesai.');
    }


    /**
     * Finalize (lock) — tulis checksum + audit
     */
    public function finalize(Request $r, PayRun $payrun)
{
    $this->authorize('finalize', $payrun);

    if ($payrun->locked_at) {
        return back()->withErrors(['error' => 'Pay run sudah terkunci.']);
    }
    if (! $payrun->items()->exists()) {
        return back()->withErrors(['error' => 'Belum ada item. Jalankan Simulate dulu.']);
    }

    $issues = app(PreFinalizeChecker::class)->check($payrun);
    if ($issues->isNotEmpty()) {
        return back()->withErrors(['error' => 'Pre-finalize gagal: '.$issues->implode('; ')]);
    }

    DB::transaction(function () use ($payrun) {
        // kunci baris untuk mencegah double-finalize
        $locked = PayRun::whereKey($payrun->id)->lockForUpdate()->first();

        if ($locked->locked_at) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'error' => 'Pay run sedang/terlanjur terkunci.',
            ]);
        }

        $payload = [
            'total'  => (float) $locked->items()->sum('net_pay'),
            'count'  => (int)   $locked->items()->count(),
            'period' => [$locked->start_date->toDateString(), $locked->end_date->toDateString()],
            'group'  => (int)   $locked->pay_group_id,
        ];

        $locked->forceFill([
            'status'    => 'finalized',
            'locked_at' => now(),
            'locked_by' => auth()->id(),
            'checksum'  => hash('sha256', json_encode($payload)),
        ])->save();

        DB::table('pay_run_audits')->insert([
            'pay_run_id' => $locked->id,
            'actor_id'   => auth()->id(),
            'action'     => 'FINALIZE',
            'before_json'=> null,
            'after_json' => json_encode($payload),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    return redirect()->route('admin.payruns.review', $payrun)->with('success','Pay run finalized & locked.');
}


    /**
     * Reopen (unlock) — audit & reset lock/checksum
     */
    public function reopen(PayRun $payrun)
    {
        $this->authorize('reopen', $payrun);

        if (! $payrun->locked_at) {
            return back()->withErrors(['error' => 'Pay run belum terkunci.']);
        }

        DB::transaction(function () use ($payrun) {
            DB::table('pay_run_audits')->insert([
                'pay_run_id' => $payrun->id,
                'actor_id'   => auth()->id(),
                'action'     => 'REOPEN',
                'before_json'=> json_encode([
                    'status'    => $payrun->status,
                    'locked_at' => $payrun->locked_at,
                    'locked_by' => $payrun->locked_by,
                    'checksum'  => $payrun->checksum,
                ]),
                'after_json' => json_encode(['status'=>'draft','locked_at'=>null,'locked_by'=>null,'checksum'=>null]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $payrun->forceFill([
                'status'    => 'draft', // atau 'simulated' jika ingin
                'locked_at' => null,
                'locked_by' => null,
                'checksum'  => null,
            ])->save();
        });

        return back()->with('success', 'Pay run dibuka kembali (unlocked).');
    }

    /**
     * Ambil daftar karyawan dalam pay group (mendukung berbagai skema relasi)
     */
    private function employeesInGroupQuery(int $groupId)
    {
        $ids  = collect();
        $used = [];

        if (Schema::hasColumn('employees', 'pay_group_id')) {
            $used[] = 'column';
            $ids = $ids->merge(Employee::where('pay_group_id', $groupId)->pluck('id'));
        }

        foreach (['employee_pay_group', 'pay_group_employee'] as $pivot) {
            if (Schema::hasTable($pivot)) {
                $used[] = $pivot;
                $ids = $ids->merge(DB::table($pivot)->where('pay_group_id', $groupId)->pluck('employee_id'));
            }
        }

        $ids = $ids->unique()->values();

        \Log::info('EMP_QUERY_BUILT', [
            'group' => $groupId,
            'sources_used' => $used,
            'count' => $ids->count(),
            'sample' => $ids->take(5),
        ]);

        return $ids->isEmpty()
            ? Employee::query()->whereRaw('1=0')
            : Employee::query()->whereIn('id', $ids);
    }

    /**
     * Normalisasi field source (json) di detail
     */
    private function normalizeSourceJson($val): string
        {
            if (is_array($val) || is_object($val)) {
                return json_encode($val, JSON_UNESCAPED_UNICODE);
            }
            return json_encode(['note' => (string) $val], JSON_UNESCAPED_UNICODE);
        }

    public function export(Request $request)
    {
        $filters = $request->only(['status','group','q']);

        // Jika mau tentukan bulan sendiri dari UI:
        // contoh ?month_label=Agustus
        if ($request->filled('month_label')) {
            $filters['month_label'] = $request->get('month_label');
        }

        $export   = new PayRunExport($filters);
        $format   = strtolower((string) $request->get('format', 'xlsx'));
        $format   = in_array($format, ['xlsx','csv'], true) ? $format : 'xlsx';
        $filename = 'payruns-' . now()->format('Ymd-His') . '.' . $format;

        return Excel::download(
            $export,
            $filename,
            $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX,
            $format === 'csv' ? ['Content-Type' => 'text/csv; charset=UTF-8'] : []
        );
    }

}
