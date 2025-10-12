<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// === Models (sesuaikan namespace jika beda) ===
use App\Models\Employee;
use App\Models\Department;
use App\Models\Section;
use App\Models\Position;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\PayRun;
use Illuminate\Support\Facades\Schema;


class DashboardController extends Controller
{
    public function index()
    {
        $now        = now();
        $startMonth = $now->copy()->startOfMonth();
        $endMonth   = $now->copy()->endOfMonth();

        /** =========================================================
         *  KPI (global overview)
         *  ========================================================= */
        $totalEmployees = Employee::count();
        $newJoiners     = Employee::whereBetween('tmt', [$startMonth, $endMonth])->count();
        $totalDept      = Department::count();
        $totalSection   = Section::count();
        $totalPosition  = Position::count();

        // Attrition (pakai contract_end_date sebagai indikator keluar)
        $exitsThisMonth = Employee::whereBetween('contract_end_date', [$startMonth, $endMonth])->count();
        $avgHeadcount   = max(1, round(($totalEmployees + $totalEmployees) / 2));
        $attritionRate  = $avgHeadcount ? ($exitsThisMonth / $avgHeadcount) * 100 : 0;

        // Overtime Cost bulan ini: durasi jam x rate (Employee::resolveComponentRate('OT'))
        $overtimes = OvertimeRequest::with('employee')
            ->where('status', 'approved')
            ->whereBetween('date', [$startMonth, $endMonth])
            ->get();

        // cek cepat apakah ada skema rate yang didukung
        $hasRateTable = Schema::hasTable('pay_component_rates');
        $hasSomeCode  = $hasRateTable && (
            Schema::hasColumn('pay_component_rates','component_code') ||
            Schema::hasColumn('pay_component_rates','code') ||
            Schema::hasColumn('pay_component_rates','pay_component_id')
        );

        $overtimeCost = $overtimes->sum(function ($ot) use ($hasSomeCode) {
            $start = \Carbon\Carbon::parse($ot->start_time);
            $end   = \Carbon\Carbon::parse($ot->end_time);
            $hours = $end->floatDiffInHours($start);

            $rate = 0;
            if ($hasSomeCode && method_exists($ot->employee, 'resolveComponentRate')) {
                try {
                    $rate = $ot->employee->resolveComponentRate('OT') ?? 0;
                } catch (\Throwable $e) {
                    // fallback aman
                    $rate = (float) config('payroll.ot_rate_per_hour', 0);
                }
            } else {
                // fallback kalau memang belum ada tabel rate
                $rate = (float) config('payroll.ot_rate_per_hour', 0);
            }

            return $hours * $rate;
        });

        // Pending approvals (leave + overtime) — tambahkan entity lain jika ada
        $pendingApprovals = LeaveRequest::where('status', 'pending')->count()
                           + OvertimeRequest::where('status', 'pending')->count();

        /** =========================================================
         *  Data sections
         *  ========================================================= */

        // Birthdays: bulan ini (urut tanggal); tampilkan max 6
        $birthdays = Employee::whereMonth('date_of_birth', $now->month)
            ->orderByRaw('DAY(date_of_birth)')
            ->take(6)->get();

        // Out of Office (Sick): dukung nama lokal "Cuti Sakit"
        $sickTypeIds = LeaveType::whereIn('name', ['Cuti Sakit', 'Sick', 'Sakit'])->pluck('id');
        $sickLeaves  = $sickTypeIds->isNotEmpty()
            ? LeaveRequest::with('employee')
                ->whereIn('leave_type_id', $sickTypeIds)
                ->whereIn('status', ['approved','pending'])
                ->where(function($q) use ($now){
                    $from = $now->copy()->subDays(14);
                    $to   = $now->copy()->addDays(14);
                    $q->whereBetween('start_date', [$from, $to])
                      ->orWhereBetween('end_date',   [$from, $to])
                      ->orWhere(function($qq) use ($now){
                          $qq->where('start_date','<=',$now)->where('end_date','>=',$now);
                      });
                })
                ->orderBy('start_date')
                ->take(6)
                ->get()
            : collect();

        // Payments (PayRun) — ambil yang finalized bulan ini untuk total expenses,
        // dan fallback menampilkan 6 payrun finalized terbaru jika bulan ini kosong.
        $payrunsMonth = PayRun::with(['payGroup','items'])
            ->whereNotNull('finalized_at')
            ->whereBetween('finalized_at', [$startMonth, $endMonth])
            ->orderByDesc('finalized_at')
            ->get();

        // Hitung total expenses bulan ini dari items, tanpa asumsi nama kolom amount.
        $totalExpenses = $payrunsMonth->sum(function($pr){
            return $pr->items->sum(function($it){
                $a = $it->getAttributes();
                foreach (['amount','total','value','net_amount','gross_amount','nominal'] as $k) {
                    if (array_key_exists($k,$a) && is_numeric($a[$k])) return (float)$a[$k];
                }
                return 0;
            });
        });

        // Payruns untuk tabel (maks 6 baris)
        if ($payrunsMonth->isEmpty()) {
            $payruns = PayRun::with(['payGroup','items'])
                ->whereNotNull('finalized_at')
                ->orderByDesc('finalized_at')
                ->take(6)
                ->get();
        } else {
            $payruns = $payrunsMonth->take(6);
        }

        /** =========================================================
         *  KPI array untuk Blade
         *  (delta optional: kamu bisa isi jika butuh tren MoM)
         *  ========================================================= */
        $kpi = [
            'total_employees' => [
                'label' => 'Total Employees',
                'value' => $totalEmployees,
                'icon'  => 'fas fa-users',
            ],
            'new_joiners' => [
                'label' => 'New Joiners',
                'value' => $newJoiners,
                'icon'  => 'fas fa-user-plus',
            ],
            'total_expenses' => [
                'label' => 'Total Expenses',
                'value' => $totalExpenses,
                'icon'  => 'fas fa-wallet',
                'hint'  => 'payruns finalized this month',
            ],
            'total_department' => [
                'label' => 'Departments',
                'value' => $totalDept,
                'icon'  => 'fas fa-sitemap',
            ],
            'total_section' => [
                'label' => 'Sections',
                'value' => $totalSection,
                'icon'  => 'fas fa-stream',
            ],
            'total_position' => [
                'label' => 'Positions',
                'value' => $totalPosition,
                'icon'  => 'fas fa-briefcase',
            ],
            'attrition_rate' => [
                'label' => 'Attrition Rate',
                'value' => round($attritionRate, 1) . '%',
                'icon'  => 'fas fa-exchange-alt',
                'hint'  => 'exits / avg headcount',
            ],
            'overtime_cost' => [
                'label' => 'Overtime Cost',
                'value' => round($overtimeCost),
                'icon'  => 'fas fa-business-time',
            ],
            'pending_approvals' => [
                'label' => 'Pending Approvals',
                'value' => $pendingApprovals,
                'icon'  => 'fas fa-inbox',
            ],
        ];

        /** =========================================================
         *  Opsional: Hero slides default (bisa kamu override dari controller)
         *  ========================================================= */
        $heroSlides = [
            [
                'title'    => 'Company Overview',
                'subtitle' => 'Track headcount, expenses & approvals',
                'bg'       => 'linear-gradient(90deg,#6d28d9,#2563eb)',
                'image'    => null,
            ],
            [
                'title'    => 'Payroll Cutoff',
                'subtitle' => 'Cutoff: '.$endMonth->format('d M Y'),
                'bg'       => 'linear-gradient(90deg,#0ea5e9,#22c55e)',
                'image'    => null,
            ],
            [
                'title'    => 'Compliance',
                'subtitle' => 'Monitor contracts & policies',
                'bg'       => 'linear-gradient(90deg,#f59e0b,#ef4444)',
                'image'    => null,
            ],
        ];

        // (Opsional) Holidays — kalau kamu punya model Holiday sendiri, inject di sini.
        $holidays = collect(); // biarkan kosong untuk sekarang

        return view('superadmin.pages.dashboard', [
            'kpi'        => $kpi,
            'birthdays'  => $birthdays,
            'sickLeaves' => $sickLeaves,
            'payruns'    => $payruns,
            'heroSlides' => $heroSlides,
            'holidays'   => $holidays,
            'payroll_cutoff'     => $endMonth->format('d M Y'),
            'contracts_expiring' => Employee::whereBetween('contract_end_date', [$startMonth,$endMonth])->count(),
        ]);
    }
}
