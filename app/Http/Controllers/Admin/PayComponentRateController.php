<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayComponent;
use App\Models\PayComponentRate;
use App\Models\PayGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PayComponentRateController extends Controller
{
    public function index(PayComponent $payComponent)
    {
        session(['last_pay_component_id' => $payComponent->id]);

        $items = PayComponentRate::with('payGroup')
            ->where('pay_component_id', $payComponent->id)
            ->orderByDesc('effective_start')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $today = now()->startOfDay();

        $activeCount = PayComponentRate::where('pay_component_id', $payComponent->id)
            ->whereDate('effective_start', '<=', $today)
            ->where(fn($q) => $q->whereNull('effective_end')->orWhereDate('effective_end','>=',$today))
            ->count();

        $payGroups = class_exists(PayGroup::class) ? PayGroup::orderBy('name')->get() : collect();

        return view('admin.pages.pay-component-rates.index', compact(
            'payComponent', 'items', 'activeCount', 'today', 'payGroups'
        ));
    }

    public function create(PayComponent $payComponent)
    {
        $model = new PayComponentRate(['effective_start' => now()->toDateString()]);
        $payGroups = class_exists(PayGroup::class) ? PayGroup::orderBy('name')->get() : collect();

        return view('admin.pages.pay-component-rates.form', compact('payComponent', 'model', 'payGroups'));
    }

    public function store(Request $r, PayComponent $payComponent)
    {
        $data = $this->mapInput($r) + ['pay_component_id' => $payComponent->id];

        $this->assertNoOverlap($data);

        PayComponentRate::create($data);

        return redirect()
            ->route('admin.pay-components.rates.index', $payComponent)
            ->with('success', 'Rate created.');
    }

    public function edit(PayComponentRate $rate)
    {
        $payComponent = $rate->component;
        $model = $rate;
        $payGroups = class_exists(PayGroup::class) ? PayGroup::orderBy('name')->get() : collect();

        return view('admin.pages.pay-component-rates.form', compact('payComponent', 'model', 'payGroups'));
    }

    public function update(Request $r, PayComponentRate $rate)
    {
        $data = $this->mapInput($r);
        $data['pay_component_id'] = $rate->pay_component_id;

        $this->assertNoOverlap($data, $rate->id);

        $rate->update($data);

        return back()->with('success', 'Rate updated.');
    }

    public function destroy(PayComponentRate $rate)
    {
        $pc = $rate->component; // pakai model agar aman
        $rate->delete();

        return redirect()
            ->route('admin.pay-components.rates.index', $pc)
            ->with('success', 'Rate removed.');
    }

    /* ================= Helpers ================= */

    private function rules(Request $r): array
    {
        // exists pay_groups hanya jika tabelnya ada
        $payGroupRule = Schema::hasTable('pay_groups')
            ? ['nullable', Rule::exists('pay_groups','id')]
            : ['nullable'];

        return $r->validate([
            'pay_group_id'     => $payGroupRule,
            'unit'             => ['required','string','max:50'],     // wajib
            'unit_custom'      => ['nullable','string','max:50'],
            'rate'             => ['required','numeric','min:0'],
            'formula'          => ['nullable','string','max:255'],
            'effective_start'  => ['required','date'],
            'effective_end'    => ['nullable','date','after_or_equal:effective_start'],
            // meta opsional
            'meta'             => ['sometimes','array'],
            'meta.basis'       => ['nullable','string','max:64'],
            'meta.cap'         => ['nullable','numeric','min:0'],
        ]);
    }

    /**
     * Gabungkan unit/unit_custom + rapikan meta dari form.
     */
    private function mapInput(Request $r): array
    {
        $v = $this->rules($r);

        // unit: bila 'custom', pakai unit_custom
        $unit = $v['unit'] === 'custom'
            ? trim((string)($v['unit_custom'] ?? ''))
            : trim((string)$v['unit']);

        if ($unit === '') {
            throw ValidationException::withMessages(['unit' => 'Unit tidak boleh kosong.']);
        }

        // meta: hanya simpan kunci yang terpakai (basis, cap)
        $meta = [];
        if (isset($v['meta']['basis']) && $v['meta']['basis'] !== '') {
            $meta['basis'] = $v['meta']['basis'];
        }
        if (isset($v['meta']['cap']) && $v['meta']['cap'] !== null && $v['meta']['cap'] !== '') {
            $meta['cap'] = (float) $v['meta']['cap'];
        }

        return [
            'pay_group_id'    => $v['pay_group_id'] ?? null,
            'unit'            => $unit,               // contoh: %, IDR, IDR/day
            'rate'            => (float)$v['rate'],   // contoh: 1 untuk 1%
            'formula'         => $v['formula'] ?? null,
            'effective_start' => $v['effective_start'],
            'effective_end'   => $v['effective_end'] ?? null,
            'meta'            => $meta,               // JSON (basis, cap)
        ];
    }

    /**
     * Cegah overlap periode pada scope (pay_component_id + pay_group_id yang sama).
     * Null dianggap -∞ / +∞ untuk perbandingan tanggal.
     */
    private function assertNoOverlap(array $data, $ignoreId = null): void
    {
        $componentId = (int) $data['pay_component_id'];
        $groupId     = $data['pay_group_id'] ?? null;

        $start = $data['effective_start'] ?? '1000-01-01';
        $end   = $data['effective_end']   ?? '9999-12-31';

        $exists = PayComponentRate::query()
            ->where('pay_component_id', $componentId)
            ->when($groupId !== null,
                fn ($q) => $q->where('pay_group_id', $groupId),
                fn ($q) => $q->whereNull('pay_group_id')
            )
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereRaw('COALESCE(effective_start, "1000-01-01") <= ?', [$end])
            ->whereRaw('COALESCE(effective_end,   "9999-12-31") >= ?', [$start])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'effective_start' => 'Periode rate bertabrakan untuk komponen (dan pay group) yang sama.',
            ]);
        }
    }
}
