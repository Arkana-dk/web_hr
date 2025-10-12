<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{LeaveLedger, LeaveType, Employee};

class LeaveLedgerController extends Controller
{
    public function index(Request $r)
    {
        $q = LeaveLedger::with(['employee.department','leaveType'])
            ->orderByDesc('entry_date')
            ->orderByDesc('id');

        // Filter tanggal -> pakai entry_date
        if ($r->filled('start'))    $q->whereDate('entry_date','>=',$r->start);
        if ($r->filled('end'))      $q->whereDate('entry_date','<=',$r->end);

        // Filter lain
        if ($r->filled('employee')) $q->where('employee_id',$r->employee);
        if ($r->filled('type'))     $q->where('leave_type_id',$r->type);

        // Filter debit/credit
        if ($r->filled('direction')) {
            // Bila kolom entry_type ada, paling simpel:
            $q->where('entry_type', strtolower($r->direction)); // 'debit'|'credit'
            // Kalau ada legacy data tanpa entry_type dan kamu ingin fallback tanda:
            // $q->orWhere(function($qq) use ($r) {
            //     $r->direction === 'debit'
            //         ? $qq->whereNull('entry_type')->where('quantity','<',0)
            //         : $qq->whereNull('entry_type')->where('quantity','>',0);
            // });
        }

        $ledgers    = $q->paginate(20)->withQueryString();
        $leaveTypes = LeaveType::orderBy('name')->get();
        $employees  = Employee::orderBy('name')->get();

        return view('admin.pages.leave-ledger.index', compact('ledgers','leaveTypes','employees'));
    }
}
