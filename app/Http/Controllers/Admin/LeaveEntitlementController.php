<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{LeaveEntitlement, LeaveType, Employee, Department};

class LeaveEntitlementController extends Controller
{
    // App\Http\Controllers\Admin\LeaveEntitlementController.php
public function index(Request $r)
{
    $q = \App\Models\LeaveEntitlement::with(['employee.department','leaveType'])
        ->when($r->filled('search'), function($qq) use ($r){
            $s = trim($r->search);
            $qq->whereHas('employee', function($w) use ($s){
                $w->where('name','like',"%{$s}%")
                  ->orWhere('employee_number','like',"%{$s}%")
                  ->orWhere('email','like',"%{$s}%");
            });
        })
        ->when($r->filled('leave_type_id'), fn($qq)=>$qq->where('leave_type_id', $r->leave_type_id))
        ->when($r->filled('year'), function($qq) use ($r){
            // filter periode dalam tahun
            $year = (int) $r->year;
            $start = \Carbon\Carbon::create($year,1,1);
            $end   = \Carbon\Carbon::create($year,12,31);
            $qq->whereDate('period_start','<=',$end)
               ->whereDate('period_end','>=',$start);
        })
        ->orderByDesc('updated_at');

    $entitlements = $q->paginate(20)->withQueryString(); // <- penting

    $leaveTypes = \App\Models\LeaveType::orderBy('name')->get();

    return view('admin.pages.leave-entitlements.index', compact('entitlements','leaveTypes'));
}


    public function adjust(Request $r)
    {
        $data = $r->validate([
            'employee_id'   => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'amount'        => 'required|numeric', // +/-
            'note'          => 'nullable|string|max:255',
        ]);

        // Implementasi sederhananya: buat/ambil ent yg aktif lalu adjust
        $ent = LeaveEntitlement::currentFor($data['employee_id'], $data['leave_type_id']); // sediakan scope/helper di model
        if(!$ent){
            return back()->withErrors(['adjust'=>'Tidak ada entitlement aktif untuk karyawan/tipe ini.']);
        }
        $ent->adjustments = (float)($ent->adjustments ?? 0) + (float)$data['amount'];
        $ent->save();

        // TODO (opsional): tulis ke LeaveLedger sebagai "adjustment"
        return back()->with('success','Adjustment berhasil disimpan.');
    }


}
