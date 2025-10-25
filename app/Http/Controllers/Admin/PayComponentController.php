<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayComponent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PayComponentController extends Controller
{
    /** Selaras dengan UI */
    private const KIND_ALLOWED = ['earning','allowance','deduction','reimbursement','statutory','info'];
    private const CALC_ALLOWED = ['fixed','hourly','percent','formula'];

    public function index(Request $r)
    {
        $items = PayComponent::query()
            ->when($r->filled('q'), function ($q) use ($r) {
                $q->where(function ($qq) use ($r) {
                    $qq->where('code', 'like', '%'.$r->q.'%')
                       ->orWhere('name', 'like', '%'.$r->q.'%');
                });
            })
            ->when($r->filled('kind'), fn ($q) => $q->where('kind', $r->kind))
            ->when($r->has('active') && $r->input('active') !== '',
                fn ($q) => $q->where('active', (bool) $r->input('active')))
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('admin.pages.pay-components.index', compact('items'));
    }

    public function create()
    {
        return view('admin.pages.pay-components.form', [
            'model' => new PayComponent(['active' => true]),
        ]);
    }

    public function store(Request $r)
    {
        $this->normalizeInput($r);
        $data = $this->validateData($r);

        $model = new PayComponent();
        $model->fill($data);
        $model->active           = $r->boolean('active', true);

        // Optional fields (abaikan jika tak ada di form)
        $model->posting_side     = $r->input('posting_side');
        $model->gl_account       = $r->input('gl_account');
        $model->cost_center      = $r->input('cost_center');
        $model->effective_start  = $r->input('effective_start');
        $model->effective_end    = $r->input('effective_end');
        $model->notes            = $r->input('notes');

        // attributes boleh array/JSON string
        if ($r->filled('attributes')) {
            $attrs = $r->input('attributes');
            $model->attributes = is_array($attrs) ? $attrs : (json_decode($attrs, true) ?: null);
        }

        $model->save();

        return redirect()->route('admin.pay-components.index')->with('success', 'Created');
    }

    public function edit(PayComponent $payComponent)
    {
        return view('admin.pages.pay-components.form', ['model' => $payComponent]);
    }

    public function update(Request $r, PayComponent $payComponent)
    {
        $this->normalizeInput($r);
        $data = $this->validateData($r, $payComponent->id);

        $payComponent->fill($data);
        $payComponent->active           = $r->boolean('active', true);
        $payComponent->posting_side     = $r->input('posting_side');
        $payComponent->gl_account       = $r->input('gl_account');
        $payComponent->cost_center      = $r->input('cost_center');
        $payComponent->effective_start  = $r->input('effective_start');
        $payComponent->effective_end    = $r->input('effective_end');
        $payComponent->notes            = $r->input('notes');

        if ($r->filled('attributes')) {
            $attrs = $r->input('attributes');
            $payComponent->attributes = is_array($attrs) ? $attrs : (json_decode($attrs, true) ?: null);
        } else {
            $payComponent->attributes = null;
        }

        $payComponent->save();

        return back()->with('success', 'Updated');
    }

    public function destroy(PayComponent $payComponent)
    {
        $payComponent->delete(); // soft-delete
        return redirect()->route('admin.pay-components.index')->with('success', 'Deleted');
    }

    /* ================= Fitur Achive dan restore  ================= */

    public function archive(PayComponent $payComponent)
{
    // Guard: pastikan benar-benar ter-bind record yang ada
    if (!$payComponent->getKey()) {
        abort(404, 'PayComponent not found for archive.');
    }

    // Pilihan A (aman & langsung UPDATE tanpa risiko INSERT):
    \App\Models\PayComponent::whereKey($payComponent->getKey())
        ->update(['active' => 0]);

    // atau Pilihan B (biasa):
    // $payComponent->active = 0;
    // $payComponent->save();

    return back()->with('success', "Komponen {$payComponent->code} diarsipkan.");
}

public function restore(PayComponent $payComponent)
{
    if (!$payComponent->getKey()) {
        abort(404, 'PayComponent not found for restore.');
    }

    \App\Models\PayComponent::whereKey($payComponent->getKey())
        ->update(['active' => 1]);

    return redirect()
        ->route('admin.pay-components.index', ['active' => '1'])
        ->with('success', "Komponen {$payComponent->code} dipulihkan.");
}

public function activate(PayComponent $payComponent)
    {
        if (!$payComponent->getKey()) {
            abort(404, 'PayComponent not found for activation.');
        }

        \App\Models\PayComponent::whereKey($payComponent->getKey())
            ->update(['active' => 1]);

        return back()->with('success', "Komponen {$payComponent->code} diaktifkan kembali.");
    }



    /* ================= Helpers ================= */

    /** Normalisasi agar konsisten dengan UI */
    private function normalizeInput(Request $r): void
    {
        // code: uppercase + spasi -> underscore
        if ($r->filled('code')) {
            $code = strtoupper(trim($r->input('code')));
            $code = preg_replace('/\s+/', '_', $code);
            $r->merge(['code' => $code]);
        }

        // calc_type: alias 'rule' -> 'formula'
        if ($r->filled('calc_type')) {
            $calc = strtolower($r->input('calc_type'));
            if ($calc === 'rule') $calc = 'formula';
            $r->merge(['calc_type' => $calc]);
        }

        // kind ke lowercase
        if ($r->filled('kind')) {
            $r->merge(['kind' => strtolower($r->input('kind'))]);
        }
    }

    private function validateData(Request $r, $ignoreId = null): array
    {
        return $r->validate([
            'code'  => [
                'required','string','max:40',
                Rule::unique('pay_components', 'code')
                    ->ignore($ignoreId)
                    ->where(fn ($q) => $q->whereNull('deleted_at')),
            ],
            'name'           => ['required','string','max:120'],
            'kind'           => ['required', Rule::in(self::KIND_ALLOWED)],
            'calc_type'      => [  'required', Rule::in(['fixed','percent','hourly','rule','formula']), // pilih yang dipakai
                            ],
            'default_amount' => ['nullable','numeric','min:0'],

            // optional
            'posting_side'    => ['nullable','string','max:20'],
            'gl_account'      => ['nullable','string','max:50'],
            'cost_center'     => ['nullable','string','max:50'],
            'effective_start' => ['nullable','date'],
            'effective_end'   => ['nullable','date','after_or_equal:effective_start'],
            'attributes'      => ['nullable'],
            'notes'           => ['nullable','string','max:255'],
            'active'          => ['nullable','boolean'],
        ]);
    }
}
