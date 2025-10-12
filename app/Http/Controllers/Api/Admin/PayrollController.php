<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\SettingOvertime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollController extends Controller
{
    public function index()
    {
        $list = Payroll::with(['employee','details'])
            ->latest('start_date')
            ->get();

        return response()->json($list);
    }

    public function store(Request $request)
    {
        $request->validate([
            'start_date'=>'required|date',
            'end_date'  =>'required|date|after_or_equal:start_date',
        ]);

        $start = $request->start_date;
        $end   = $request->end_date;
        $rate  = SettingOvertime::first()->rate_per_hour ?? 0;

        $emps = Employee::with(['allowances','deductions','recruitment'])->get();

        DB::transaction(function() use($emps,$start,$end,$rate) {
            foreach ($emps as $emp) {
                if (Payroll::where('employee_id',$emp->id)
                           ->where('start_date',$start)
                           ->where('end_date',$end)
                           ->exists()) {
                    continue;
                }

                $basic = $emp->salary > 0
                         ? $emp->salary
                         : optional($emp->recruitment)->salary ?? 0;

                $allow = $emp->allowances->sum('amount');
                $deduc = $emp->deductions->sum('amount');

                $overtime = OvertimeRequest::where('employee_id',$emp->id)
                    ->where('status','approved')
                    ->whereBetween('date',[$start,$end])
                    ->get()
                    ->reduce(function($sum,$r) use($rate){
                        $hrs = Carbon::parse($r->start_time)
                                     ->floatDiffInHours($r->end_time);
                        return $sum + ($hrs * $rate);
                    }, 0);

                $net = $basic + $allow + $overtime - $deduc;

                $pr = Payroll::create([
                    'employee_id'      => $emp->id,
                    'start_date'       => $start,
                    'end_date'         => $end,
                    'basic_salary'     => $basic,
                    'total_allowances' => $allow,
                    'total_deductions' => $deduc,
                    'overtime_amount'  => $overtime,
                    'net_salary'       => $net,
                    'status'           => 'pending',
                ]);

                foreach ($emp->allowances as $a) {
                    PayrollDetail::create([
                        'payroll_id'     => $pr->id,
                        'component_name' => $a->tunjangan->name,
                        'amount'         => $a->amount,
                        'component_type' => 'allowance',
                    ]);
                }
                foreach ($emp->deductions as $d) {
                    PayrollDetail::create([
                        'payroll_id'     => $pr->id,
                        'component_name' => $d->potongan->name,
                        'amount'         => $d->amount,
                        'component_type' => 'deduction',
                    ]);
                }
                if ($overtime > 0) {
                    PayrollDetail::create([
                        'payroll_id'     => $pr->id,
                        'component_name' => 'Lembur',
                        'amount'         => $overtime,
                        'component_type' => 'overtime',
                    ]);
                }
            }
        });

        return response()->json(['message'=>'Payroll berhasil diproses.'], 201);
    }

    public function destroyAll()
    {
        DB::transaction(function(){
            PayrollDetail::query()->delete();
            Payroll::query()->delete();
        });

        return response()->json(['message'=>'Semua payroll dihapus.']);
    }

    public function show($id)
    {
        $pay = Payroll::with(['employee','details'])->findOrFail($id);
        return response()->json($pay);
    }

    public function approve($id)
    {
        $p = Payroll::findOrFail($id);
        $p->update(['status'=>'approved']);
        return response()->json(['message'=>'Payroll disetujui.']);
    }
}
