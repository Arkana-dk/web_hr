<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayGroup;
use App\Models\PayComponent;
use App\Models\PayGroupComponent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PayGroupComponentController extends Controller
{
    public function index(PayGroup $payGroup)
    {
        $items = PayGroupComponent::with('component')
            ->where('pay_group_id', $payGroup->id)
            ->ordered() // scope di model ->orderBy('sequence')
            ->paginate(20)
            ->withQueryString();

        $components = PayComponent::active()
            ->orderBy('name')
            ->get();

        return view('admin.pages.pay-group-components.index', compact('payGroup', 'items', 'components'));
    }

    public function create(PayGroup $payGroup)
    {
        $components = PayComponent::active()->orderBy('name')->get();

        return view('admin.pages.pay-group-components.form', [
            'payGroup'   => $payGroup,
            'components' => $components,
            'model'      => new PayGroupComponent([
                'sequence'  => 0,
                'mandatory' => true,
                'active'    => true,
            ]),
        ]);
    }

    public function store(Request $r, PayGroup $payGroup)
    {
        // Validasi + cegah duplikasi pay_component_id di group yang sama
        $data = $r->validate([
            'pay_component_id' => [
                'required',
                'exists:pay_components,id',
                Rule::unique('pay_group_components', 'pay_component_id')
                    ->where(fn ($q) => $q->where('pay_group_id', $payGroup->id)),
            ],
            'sequence'  => ['nullable', 'integer', 'min:0'],
            'mandatory' => ['nullable', 'boolean'],
            'active'    => ['nullable', 'boolean'],
            'notes'     => ['nullable', 'string', 'max:2000'],
        ]);

        $payload = [
            'pay_group_id'    => $payGroup->id,
            'pay_component_id'=> $data['pay_component_id'],
            'sequence'        => $data['sequence'] ?? 0,
            'mandatory'       => $r->boolean('mandatory', true),
            'active'          => $r->boolean('active', true),
            'notes'           => $data['notes'] ?? null,
        ];

        PayGroupComponent::create($payload);

        return redirect()
            ->route('admin.pay-groups.components.index', $payGroup)
            ->with('success', 'Component linked to group.');
    }

    // shallow route: /admin/pay-group-components/{component}/edit
    public function edit(PayGroupComponent $component)
    {
        $payGroup   = $component->payGroup; // gunakan relasi yang benar
        $components = PayComponent::active()->orderBy('name')->get();

        return view('admin.pages.pay-group-components.form', [
            'payGroup'   => $payGroup,
            'components' => $components,
            'model'      => $component,
        ]);
    }

    public function update(Request $r, PayGroupComponent $component)
    {
        // Validasi + tetap cegah duplikasi dalam group yg sama saat update
        $data = $r->validate([
            'pay_component_id' => [
                'required',
                'exists:pay_components,id',
                Rule::unique('pay_group_components', 'pay_component_id')
                    ->ignore($component->id)
                    ->where(fn ($q) => $q->where('pay_group_id', $component->pay_group_id)),
            ],
            'sequence'  => ['nullable', 'integer', 'min:0'],
            'mandatory' => ['nullable', 'boolean'],
            'active'    => ['nullable', 'boolean'],
            'notes'     => ['nullable', 'string', 'max:2000'],
        ]);

        $component->update([
            'pay_component_id' => $data['pay_component_id'],
            'sequence'         => $data['sequence'] ?? 0,
            'mandatory'        => $r->boolean('mandatory', true),
            'active'           => $r->boolean('active', true),
            'notes'            => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Updated.');
    }

    public function destroy(PayGroupComponent $component)
    {
        $pg = $component->payGroup; // simpan untuk redirect
        $component->delete();

        return redirect()
            ->route('admin.pay-groups.components.index', $pg)
            ->with('success', 'Removed from group.');
    }
}
