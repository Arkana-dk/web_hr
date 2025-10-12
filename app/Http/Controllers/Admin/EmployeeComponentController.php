<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeComponent;
use App\Models\Employee;
use App\Models\PayComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeComponentController extends Controller
{
    public function index(Request $r)
    {
        $q = EmployeeComponent::with(['employee', 'component'])->latest();

        // Filter opsional
        if ($r->filled('employee_id')) {
            $q->where('employee_id', $r->employee_id);
        }
        if ($r->filled('pay_component_id')) {
            $q->where('pay_component_id', $r->pay_component_id);
        }
        if ($r->has('active') && $r->input('active') !== '') {
            $q->where('active', (bool)$r->input('active'));
        }
        // Tampilkan yg efektif pada tanggal tertentu
        if ($r->filled('date')) {
            $date = $r->input('date');
            $q->where(function ($qq) use ($date) {
                $qq->whereNull('effective_start')
                   ->orWhereDate('effective_start', '<=', $date);
            })->where(function ($qq) use ($date) {
                $qq->whereNull('effective_end')
                   ->orWhereDate('effective_end', '>=', $date);
            });
        }

        $items = $q->paginate(20)->withQueryString();

        $employees   = Employee::orderBy('name')->limit(200)->get(); // sesuaikan field name
        $components  = PayComponent::active()->orderBy('name')->get();

        return view('admin.pages.employee-components.index', compact('items', 'employees', 'components'));
    }

    public function create(Request $r)
    {
        $employees  = Employee::orderBy('name')->limit(200)->get();
        $components = PayComponent::active()->orderBy('name')->get();

        $model = new EmployeeComponent([
            'active' => true,
        ]);

        // Prefill dari query string (opsional)
        if ($r->filled('employee_id')) {
            $model->employee_id = (int) $r->employee_id;
        }
        if ($r->filled('pay_component_id')) {
            $model->pay_component_id = (int) $r->pay_component_id;
        }

        return view('admin.pages.employee-components.form', compact('model', 'employees', 'components'));
    }

    public function store(Request $r)
    {
        $data = $this->rules($r);

        // Validasi tambahan: minimal satu nilai override harus diisi
        $this->ensureAtLeastOneValue($r);

        // Cek overlap periode untuk employee+component yang sama
        $this->ensureNoOverlap($r);

        $payload = [
            'employee_id'      => $data['employee_id'],
            'pay_component_id' => $data['pay_component_id'],
            'override_amount'  => $data['override_amount'] ?? null,
            'override_rate'    => $data['override_rate'] ?? null,
            'override_percent' => $data['override_percent'] ?? null,
            'override_formula' => $data['override_formula'] ?? null,
            'effective_start'  => $data['effective_start'] ?? null,
            'effective_end'    => $data['effective_end'] ?? null,
            'active'           => $r->boolean('active', true),
        ];

        EmployeeComponent::create($payload);

        return redirect()->route('admin.employee-components.index')
            ->with('success', 'Override created.');
    }

    public function edit(EmployeeComponent $employeeComponent)
    {
        $employees  = Employee::orderBy('name')->limit(200)->get();
        $components = PayComponent::active()->orderBy('name')->get();

        return view('admin.pages.employee-components.form', [
            'model'      => $employeeComponent,
            'employees'  => $employees,
            'components' => $components,
        ]);
    }

    public function update(Request $r, EmployeeComponent $employeeComponent)
    {
        $data = $this->rules($r, $employeeComponent->id);

        $this->ensureAtLeastOneValue($r);
        $this->ensureNoOverlap($r, $employeeComponent->id);

        $employeeComponent->update([
            'employee_id'      => $data['employee_id'],
            'pay_component_id' => $data['pay_component_id'],
            'override_amount'  => $data['override_amount'] ?? null,
            'override_rate'    => $data['override_rate'] ?? null,
            'override_percent' => $data['override_percent'] ?? null,
            'override_formula' => $data['override_formula'] ?? null,
            'effective_start'  => $data['effective_start'] ?? null,
            'effective_end'    => $data['effective_end'] ?? null,
            'active'           => $r->boolean('active', true),
        ]);

        return back()->with('success', 'Override updated.');
    }

    public function destroy(EmployeeComponent $employeeComponent)
    {
        $employeeComponent->delete();
        return redirect()->route('admin.employee-components.index')->with('success', 'Override removed.');
    }

    /* ================= Helpers ================= */

    private function rules(Request $r, $ignoreId = null): array
    {
        return $r->validate([
            'employee_id'      => ['required', 'exists:employees,id'],
            'pay_component_id' => ['required', 'exists:pay_components,id'],
            'override_amount'  => ['nullable', 'numeric', 'min:0'],
            'override_rate'    => ['nullable', 'numeric'],
            'override_percent' => ['nullable', 'numeric'], // contoh: 0.15 = 15%
            'override_formula' => ['nullable', 'string'],
            'effective_start'  => ['nullable', 'date'],
            'effective_end'    => ['nullable', 'date', 'after_or_equal:effective_start'],
            'active'           => ['nullable', 'boolean'],

            // Cegah duplikasi total exact (opsional): kombinasi 1 karyawan + 1 komponen + rentang yang sama
            // Rule::unique(...) bisa ditambah jika kamu ingin strict unique.
        ]);
    }

    /**
     * Minimal satu dari amount/rate/percent/formula harus diisi.
     */
    private function ensureAtLeastOneValue(Request $r): void
    {
        if (
            $r->filled('override_amount') ||
            $r->filled('override_rate') ||
            $r->filled('override_percent') ||
            $r->filled('override_formula')
        ) {
            return;
        }

        abort(422, 'Isi minimal salah satu: override_amount / override_rate / override_percent / override_formula.');
    }

    /**
     * Cek overlap periode untuk employee + component yang sama.
     * Null start dianggap -infinity (1000-01-01), null end dianggap +infinity (9999-12-31).
     */
    private function ensureNoOverlap(Request $r, $ignoreId = null): void
    {
        $employeeId = (int) $r->input('employee_id');
        $componentId = (int) $r->input('pay_component_id');

        $start = $r->input('effective_start') ?: '1000-01-01';
        $end   = $r->input('effective_end')   ?: '9999-12-31';

        $exists = EmployeeComponent::query()
            ->where('employee_id', $employeeId)
            ->where('pay_component_id', $componentId)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereRaw('COALESCE(effective_start, "1000-01-01") <= ?', [$end])
            ->whereRaw('COALESCE(effective_end, "9999-12-31") >= ?', [$start])
            ->exists();

        if ($exists) {
            abort(422, 'Periode override bertabrakan dengan data lain untuk karyawan & komponen yang sama.');
        }
    }
}
