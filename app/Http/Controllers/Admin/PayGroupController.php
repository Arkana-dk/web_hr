<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PayGroupController extends Controller
{
    public function index(Request $r)
    {
        $items = PayGroup::query()
            ->when($r->has('active') && $r->input('active')!=='', fn($q)=>$q->where('active',(bool)$r->input('active')))
            ->when($r->filled('q'), fn($q)=>$q->where(function($qq) use($r){
                $qq->where('code','like','%'.$r->q.'%')->orWhere('name','like','%'.$r->q.'%');
            }))
            ->latest()->paginate(12)->withQueryString();

        return view('admin.pages.pay-groups.index', compact('items'));
    }

    public function create()
    {
        return view('admin.pages.pay-groups.form', ['model'=> new PayGroup(['active'=>true])]);
    }

    public function store(Request $r)
    {
        $data = $this->rules($r);
        $data['active'] = $r->boolean('active', true);
        PayGroup::create($data);
        return redirect()->route('admin.pay-groups.index')->with('success','Created');
    }

    public function edit(PayGroup $payGroup)
    {
        return view('admin.pages.pay-groups.form', ['model'=>$payGroup]);
    }

    public function update(Request $r, PayGroup $payGroup)
    {
        $data = $this->rules($r, $payGroup->id);
        $data['active'] = $r->boolean('active', true);
        $payGroup->update($data);
        return back()->with('success','Updated');
    }

    public function destroy(PayGroup $payGroup)
    {
        $payGroup->delete();
        return redirect()->route('admin.pay-groups.index')->with('success','Archived');
    }

    private function rules(Request $r, $ignoreId=null): array
    {
        return $r->validate([
            'code' => ['required','string','max:30', Rule::unique('pay_groups','code')->ignore($ignoreId)],
            'name' => ['required','string','max:120'],
            'notes'=> ['nullable','string','max:2000'],
            'active'=> ['nullable','boolean'],
        ]);
    }
}
