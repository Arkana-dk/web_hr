<?php

namespace App\Domain\Payroll\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Employee;
use App\Models\{PayRun, PayGroup, PayGroupComponent, PayComponent, PayComponentRate};
use Illuminate\Support\Arr;
use App\Models\OvertimeRequest; // optional kalau pakai Eloquent

/**
 * PayContextFactory
 *
 * Bertugas membangun context ($ctx) untuk kalkulasi payroll.
 * Context ini dipakai oleh rules (OvertimeRule, LatePenaltyRule, dsb.).
 */

class PayContextFactory
{
     public static function makeFromPayRun(PayRun $run): array
    {
        // 1) Ambil pay group & periode dari pay run
        $group = PayGroup::findOrFail($run->pay_group_id);

        // 2) Ambil komponen aktif di group (urut sesuai sequence)
        $groupComponents = PayGroupComponent::with('component')
            ->where('pay_group_id', $group->id)
            ->where('active', true)
            ->orderBy('sequence')
            ->get();

        // 3) Buat kamus komponen by code (BASIC, OVERTIME, dsb.)
        $componentsByCode = PayComponent::pluck('id', 'code');

        // 4) Susun map rate per komponen yang masih berlaku dalam periode
        $rates = PayComponentRate::query()
            ->where('effective_start', '<=', $run->end_date)
            ->where(function ($q) use ($run) {
                $q->whereNull('effective_end')->orWhere('effective_end', '>=', $run->start_date);
            })
            ->get();

        $rateMap = [];
        foreach ($rates as $r) {
            $bucket = $r->pay_group_id ?? 'default';
            $rateMap[$r->pay_component_id][$bucket][] = $r;
        }

        // 5) Attendance stats: scheduled days, paid days, dll.
        $attendanceStats = self::makeAttendanceStatsFromDB($run, $group);
        if (empty($attendanceStats)) {
            $attendanceStats = self::makePlaceholderAttendanceStats($run, $group);
        }

        $periodStart = Carbon::parse($run->start_date);
        $periodEnd   = Carbon::parse($run->end_date);

       // 7) Konteks PPh21
        $ctxPph21 = [
            'enable_true_up' => true, // aktifkan true-up Desember/resign
            'ytd_neto'       => 0.0,  // TODO: isi dari akumulasi neto pegawai YTD
            'ytd_pph21'      => 0.0,  // TODO: isi dari akumulasi PPh21 YTD
            'status_ptkp'    => null, // <--- tambahkan default
        ];

        // Ambil PTKP dari employee (kalau ada kolom di tabel employees)
        if (Schema::hasColumn('employees', 'status_ptkp')) {
            $emp = Employee::find($run->employee_id ?? null);
            if ($emp) {
                $ctxPph21['status_ptkp'] = $emp->status_ptkp ?? 'TK/0';
            }
        }


        // 6) Return context lengkap
        return [
            'period'             => ['start' => $run->start_date, 'end' => $run->end_date],
            'pay_group'          => $group,
            'group_components'   => $groupComponents,
            'components_by_code' => $componentsByCode,
            'rates'              => $rateMap,
            'attendanceStats'    => $attendanceStats,

            // Jam lembur karyawan (dari overtime_requests)
            'overtime_hours'     => self::buildOvertimeMap($periodStart, $periodEnd),

            // Menit keterlambatan karyawan (dari attendances)
            'late_minutes'       => self::buildLateMinutesMap($periodStart, $periodEnd, $group),

            // Kebijakan default penalti keterlambatan
            'late_policy'        => [
                'grace_minutes'      => 5,     // toleransi tanpa potongan
                'rounding'           => 'none',// none=per menit; bisa 'ceil-15' dll.
                'period_cap_minutes' => 60,    // maksimal dipotong per periode
            ],
            'pph21'              => $ctxPph21, // <--- tambahan penting
        ];
    }

     // -------------------------------------------------------------------------
    // Attendance Stats (scheduled_days, paid_days, dll.)
    // -------------------------------------------------------------------------
    /**
     * Hitung scheduled_days/present_days/paid_days dari:
     * - attendances (status + check_in)
     * - attendance_requests (approved; single/range; optional portion)
     * - leave_requests (approved; single/range; optional portion)
     * Default workdays: Senin–Jumat.
     */
    private static function makeAttendanceStatsFromDB(PayRun $run, PayGroup $group): array
    {
        if (!Schema::hasTable('attendances')) {
            return [];
        }

        $dailyHours = (float) config('payroll.default_daily_hours', 8);

        $start = $run->start_date;
        $end   = $run->end_date;

        // Employees di pay group ini (kolom langsung ATAU pivot)
        $employeeIds = self::employeeIdsInGroup($group->id);
        if ($employeeIds->isEmpty()) {
            return [];
        }

        // Workdays default: Senin–Jumat
        $period = CarbonPeriod::create($start, $end);
        $scheduledDays = 0;
        $weekdayDates = [];
        foreach ($period as $d) {
            if ($d->isWeekday()) {
                $scheduledDays++;
                $weekdayDates[] = $d->toDateString();
            }
        }
        $weekdayDates = collect($weekdayDates);

        // ---- ATTENDANCES ----
        $attCols = Schema::getColumnListing('attendances');
        $dateColAtt   = self::firstCol($attCols, ['date','work_date','attendance_date']);
        $statusColAtt = self::firstCol($attCols, ['status','state']);
        $inColAtt     = self::firstCol($attCols, ['check_in_at','check_in_time','check_in','time_in','clock_in_at','in_time']);
        $hasSoftDeleteAtt = in_array('deleted_at', $attCols, true);
        if (!$dateColAtt) return [];

        $attQuery = DB::table('attendances')
            ->select(array_values(array_filter([
                'employee_id',
                $dateColAtt,
                $statusColAtt,
                $inColAtt,
            ])))
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween($dateColAtt, [$start, $end]);
        if ($hasSoftDeleteAtt) $attQuery->whereNull('deleted_at');

        $attRows = $attQuery->get()->groupBy('employee_id');

        // ---- ATTENDANCE REQUESTS (entitlement) ----
        $expandedAttendanceReqs = collect();
        if (Schema::hasTable('attendance_requests')) {
            $reqCols = Schema::getColumnListing('attendance_requests');
            $dateColReq   = self::firstCol($reqCols, ['date','request_date']);
            $startColReq  = self::firstCol($reqCols, ['start_date','from_date','begin_date']);
            $endColReq    = self::firstCol($reqCols, ['end_date','to_date','finish_date']);
            $typeColReq   = self::firstCol($reqCols, ['type','leave_type','request_type']);
            $statusColReq = self::firstCol($reqCols, ['status','approval_status','state']);
            $portionCol   = self::firstCol($reqCols, ['portion','duration','hours']); // opsional
            $hasSoftDel   = in_array('deleted_at', $reqCols, true);

            if ($typeColReq && $statusColReq && ($dateColReq || ($startColReq && $endColReq))) {
                $selects = ['employee_id', $typeColReq.' as __type', $statusColReq.' as __status'];
                if ($dateColReq)  $selects[] = $dateColReq.' as __date';
                if ($startColReq) $selects[] = $startColReq.' as __start';
                if ($endColReq)   $selects[] = $endColReq.' as __end';
                if ($portionCol)  $selects[] = $portionCol.' as __portion';

                $q = DB::table('attendance_requests')->select($selects)->whereIn('employee_id', $employeeIds);
                if ($dateColReq) {
                    $q->whereBetween($dateColReq, [$start, $end]);
                } else {
                    $q->where(function($qq) use($startColReq,$endColReq,$start,$end){
                        $qq->where($startColReq, '<=', $end)->where($endColReq, '>=', $start);
                    });
                }
                if ($hasSoftDel) $q->whereNull('deleted_at');

                $reqRaw = $q->get();
                foreach ($reqRaw as $r) {
                    $dates = [];
                    if (isset($r->__date)) {
                        $dates = [$r->__date];
                    } else {
                        $p = CarbonPeriod::create($r->__start, $r->__end);
                        foreach ($p as $d) $dates[] = $d->toDateString();
                    }
                    foreach ($dates as $d) {
                        if (!$weekdayDates->contains($d)) continue;
                        $expandedAttendanceReqs->push((object)[
                            'employee_id' => $r->employee_id,
                            '__date'      => $d,
                            '__type'      => $r->__type,
                            '__status'    => $r->__status,
                            '__portion'   => property_exists($r,'__portion') ? $r->__portion : null,
                            '__source'    => 'attendance_requests',
                        ]);
                    }
                }
            }
        }

        // ---- LEAVE REQUESTS (entitlement) ----
        $expandedLeaveReqs = collect();
        if (Schema::hasTable('leave_requests')) {
            $lvCols = Schema::getColumnListing('leave_requests');
            $dateColLv   = self::firstCol($lvCols, ['date','request_date']);
            $startColLv  = self::firstCol($lvCols, ['start_date','from_date','begin_date']);
            $endColLv    = self::firstCol($lvCols, ['end_date','to_date','finish_date']);
            $typeColLv   = self::firstCol($lvCols, ['type','leave_type','request_type']);
            $statusColLv = self::firstCol($lvCols, ['status','approval_status','state']);
            $portionColLv= self::firstCol($lvCols, ['portion','day_portion','duration']); // opsional
            $hasSoftDel  = in_array('deleted_at', $lvCols, true);

            if ($typeColLv && $statusColLv && ($dateColLv || ($startColLv && $endColLv))) {
                $selects = ['employee_id', $typeColLv.' as __type', $statusColLv.' as __status'];
                if ($dateColLv)  $selects[] = $dateColLv.' as __date';
                if ($startColLv) $selects[] = $startColLv.' as __start';
                if ($endColLv)   $selects[] = $endColLv.' as __end';
                if ($portionColLv) $selects[] = $portionColLv.' as __portion';

                $q = DB::table('leave_requests')->select($selects)->whereIn('employee_id', $employeeIds);
                if ($dateColLv) {
                    $q->whereBetween($dateColLv, [$start, $end]);
                } else {
                    $q->where(function($qq) use($startColLv,$endColLv,$start,$end){
                        $qq->where($startColLv, '<=', $end)->where($endColLv, '>=', $start);
                    });
                }
                if ($hasSoftDel) $q->whereNull('deleted_at');

                $lvRaw = $q->get();
                foreach ($lvRaw as $r) {
                    $dates = [];
                    if (isset($r->__date)) {
                        $dates = [$r->__date];
                    } else {
                        $p = CarbonPeriod::create($r->__start, $r->__end);
                        foreach ($p as $d) $dates[] = $d->toDateString();
                    }
                    foreach ($dates as $d) {
                        if (!$weekdayDates->contains($d)) continue;
                        $expandedLeaveReqs->push((object)[
                            'employee_id' => $r->employee_id,
                            '__date'      => $d,
                            '__type'      => $r->__type,
                            '__status'    => $r->__status,
                            '__portion'   => property_exists($r,'__portion') ? $r->__portion : null,
                            '__source'    => 'leave_requests',
                        ]);
                    }
                }
            }
        }

        // Gabungkan entitlements
        $allEntitlements = $expandedAttendanceReqs->merge($expandedLeaveReqs)->groupBy('employee_id');

        // Mapping status/tipe dari config
        $presentStatuses  = array_map('strtolower', config('payroll.present_statuses',  ['present','late','hadir','work','wfo','onsite','wfh','remote']));
        $paidStatuses     = array_map('strtolower', config('payroll.paid_statuses',     array_merge($presentStatuses, ['leave','izin','cuti','sick','sakit','training','excused'])));
        $approvedStatuses = array_map('strtolower', config('payroll.approved_statuses', ['approved','acc','accepted','disetujui','approved_by_hr','approved_manager']));
        $paidReqTypes     = array_map('strtolower', config('payroll.paid_req_types',    ['annual_leave','paid_leave','leave','izin','cuti','sick','sakit','wfh','remote','training','marriage','maternity','paternity']));
        $unpaidReqTypes   = array_map('strtolower', config('payroll.unpaid_req_types',  ['unpaid_leave','alpha','absent']));

        $stats = [];
        foreach ($employeeIds as $eid) {
            $presentDates = collect();
            $paidDates    = collect();

            $paidHoursExtra   = 0.0; // half-day entitlement
            $workedHoursExtra = 0.0; // half-day WFH/training dianggap worked

            // A) Attendance (realisasi)
            foreach ($attRows->get($eid, collect()) as $r) {
                $date   = (string) $r->{$dateColAtt};
                if (!$weekdayDates->contains($date)) continue;

                $status = strtolower((string) ($statusColAtt ? $r->{$statusColAtt} : ''));
                $hasIn  = $inColAtt ? !empty($r->{$inColAtt}) : false;

                if ($hasIn || in_array($status, $presentStatuses, true)) {
                    $presentDates->push($date);
                    $paidDates->push($date);
                } elseif ($statusColAtt && in_array($status, $paidStatuses, true)) {
                    $paidDates->push($date);
                }
            }

            // B) Entitlements (attendance_requests + leave_requests)
            foreach ($allEntitlements->get($eid, collect()) as $r) {
                $date   = (string) $r->__date;
                if (!$weekdayDates->contains($date)) continue;

                $type   = strtolower((string) $r->__type);
                $status = strtolower((string) $r->__status);

                if (!in_array($status, $approvedStatuses, true)) continue;

                if (in_array($type, $paidReqTypes, true)) {
                    $portion = is_numeric($r->__portion ?? null) ? (float)$r->__portion : null;
                    if ($portion !== null && $portion > 0 && $portion < 1) {
                        $paidHoursExtra   += $dailyHours * $portion;
                        if (in_array($type, ['wfh','remote','training'], true)) {
                            $workedHoursExtra += $dailyHours * $portion;
                        }
                    } else {
                        $paidDates->push($date);
                    }
                }
                // unpaid → tidak menambah paidDates
            }

            $presentDays = $presentDates->unique()->count();
            $paidDays    = $paidDates->unique()->count();

            $scheduled_hours = $scheduledDays * $dailyHours;
            $paid_hours      = min($paidDays, $scheduledDays) * $dailyHours + $paidHoursExtra;
            $worked_hours    = $presentDays * $dailyHours + $workedHoursExtra;

            $stats[$eid] = [
                'scheduled_days'  => $scheduledDays,
                'paid_days'       => min($paidDays, $scheduledDays),
                'scheduled_hours' => $scheduled_hours,
                'paid_hours'      => $paid_hours,
                'workdays'        => $scheduledDays,
                'present_days'    => $presentDays,
                'worked_hours'    => $worked_hours,
            ];
        }

        return $stats;
    }

    /** Fallback: full-pay weekdays dengan jam default (tanpa schedule) */
    private static function makePlaceholderAttendanceStats(PayRun $run, PayGroup $group): array
    {
        $dailyHours = (float) config('payroll.default_daily_hours', 8);

        $period = CarbonPeriod::create($run->start_date, $run->end_date);
        $scheduledDays = 0;
        foreach ($period as $d) {
            if ($d->isWeekday()) $scheduledDays++;
        }
        $scheduledHours = $scheduledDays * $dailyHours;

        $paidDays  = $scheduledDays;
        $paidHours = $scheduledHours;

        // gunakan helper juga di fallback
        $employeeIds = self::employeeIdsInGroup($group->id);

        $stats = [];
        foreach ($employeeIds as $eid) {
            $stats[$eid] = [
                'scheduled_days'  => $scheduledDays,
                'paid_days'       => $paidDays,
                'scheduled_hours' => $scheduledHours,
                'paid_hours'      => $paidHours,
                'workdays'        => $scheduledDays,
                'present_days'    => $paidDays,
                'worked_hours'    => $paidHours,
            ];
        }

        return $stats;
    }
    // -------------------------------------------------------------------------
    // Helper umum
    // -------------------------------------------------------------------------

    /** Ambil kolom pertama yang tersedia dari kandidat */
    private static function firstCol(array $available, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if (in_array($c, $available, true)) return $c;
        }
        return null;
    }

    /** Helper: ambil IDs karyawan dalam group (kolom langsung atau pivot) */
    private static function employeeIdsInGroup(int $groupId)
    {
        // Skema 1: kolom langsung di employees
        if (Schema::hasColumn('employees', 'pay_group_id')) {
            return Employee::where('pay_group_id', $groupId)->pluck('id');
        }

        // Skema 2: pivot employee_pay_group
        if (Schema::hasTable('employee_pay_group')) {
            return DB::table('employee_pay_group')
                ->where('pay_group_id', $groupId)
                ->pluck('employee_id');
        }

        return collect();
    }

    // -------------------------------------------------------------------------
    // Overtime Aggregator
    // -------------------------------------------------------------------------
    // === Tambahkan method ini di dalam class PayContextFactory ===
    protected static function buildOvertimeMap(\Carbon\Carbon $start, \Carbon\Carbon $end): array
    {
        if (!\Schema::hasTable('overtime_requests')) return [];

        $cols = \Schema::getColumnListing('overtime_requests');
        if (empty($cols)) return [];

        // Kolom tanggal & status yang mungkin berbeda-beda namanya
        $dateCol   = collect(['date','ot_date','work_date'])->first(fn($c)=>in_array($c,$cols,true));
        $statusCol = collect(['status','approval_status'])->first(fn($c)=>in_array($c,$cols,true));

        if (!$dateCol) return [];

        $q = \DB::table('overtime_requests')->whereBetween($dateCol, [
            $start->toDateString(), $end->toDateString()
        ]);

        if ($statusCol) {
            $approvedVals = ['approved','approve','APPROVED','APPROVE','accepted','ACC'];
            $q->whereIn($statusCol, $approvedVals);
        }

        // Sumber jam lembur: hours / duration_hours / total_hours, atau hitung dari start_time–end_time
        $hourCol = collect(['hours','duration_hours','total_hours'])->first(fn($c)=>in_array($c,$cols,true));
        if ($hourCol) {
            $q->selectRaw('employee_id, SUM('.$hourCol.') AS total_hours');
        } else {
            $startCol = collect(['start_time','time_start','started_at'])->first(fn($c)=>in_array($c,$cols,true));
            $endCol   = collect(['end_time','time_end','ended_at'])->first(fn($c)=>in_array($c,$cols,true));
            if (!$startCol || !$endCol) return [];
            // TIMESTAMPDIFF dalam menit -> konversi jam
            $q->selectRaw('employee_id, SUM(TIMESTAMPDIFF(MINUTE, '.$startCol.', '.$endCol.'))/60 AS total_hours');
        }

        return $q->groupBy('employee_id')
                ->get()
                ->mapWithKeys(fn($r)=>[(int)$r->employee_id => (float)($r->total_hours ?? 0)])
                ->all();
    }

    
    // -------------------------------------------------------------------------
    // Helper untuk ambil rate BASIC
    // -------------------------------------------------------------------------

    /** Kodel baru Helpoer untuk bisa ambil daily rate basic */

     /**
     * Ambil nominal BASIC bulanan yang berlaku pada periode & pay group.
     * Menerima rate unit: amount | month | monthly | bulan.
     * Return null jika tidak ditemukan/invalid.
     */
    public function resolveBasicMonthlyAmount(array $ctx): ?float
    {
        // Ambil periode
        $start = data_get($ctx, 'period.start');   // \Carbon\Carbon atau string Y-m-d
        $end   = data_get($ctx, 'period.end');

        // Normalisasi ke string tanggal
        $startStr = \Illuminate\Support\Carbon::parse($start)->toDateString();
        $endStr   = \Illuminate\Support\Carbon::parse($end)->toDateString();

        // Ambil pay group & komponen BASIC
        $groupId   = data_get($ctx, 'pay_group.id');
        $basicComp = PayComponent::query()->where('code', 'BASIC')->first();
        if (!$basicComp) return null;

        // Cari rate BASIC yang efektif untuk periode & pay group
        $rate = PayComponentRate::query()
            ->where('pay_component_id', $basicComp->id)
            ->when($groupId, fn($q) => $q->where('pay_group_id', $groupId))
            // efektif_from <= periodeEnd && (efektif_to null atau >= periodeStart)
            ->where('effective_from', '<=', $endStr)
            ->where(function ($q) use ($startStr) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $startStr);
            })
            ->orderByDesc('effective_from')
            ->first();

        if (!$rate) return null;

        $unit = strtolower((string) $rate->unit);
        if (in_array($unit, ['amount', 'month', 'monthly', 'bulan', ''], true)) {
            return (float) $rate->rate;
        }

        // Unit lain (daily/percent/etc) bukan target helper ini → return null
        return null;
    }

    /**
     * Hitung daily rate BASIC untuk periode di context.
     * Rumus: basic_monthly / max(1, scheduled_days)
     */
    public function computeDailyBasicRate(array $ctx): ?float
    {
        $basicMonthly   = $this->resolveBasicMonthlyAmount($ctx);
        $scheduledDays  = (int) (data_get($ctx, 'scheduled_days', 0) ?: 0);

        if ($basicMonthly === null || $scheduledDays <= 0) {
            return null; // tidak bisa dihitung
        }

        // Biarkan desimal; pembulatan urusan layer tampilan
        return $basicMonthly / $scheduledDays;
    }

    // -------------------------------------------------------------------------
    // Late Minutes Aggregator
    // -------------------------------------------------------------------------

    protected static function buildLateMinutesMap(Carbon $start, Carbon $end, PayGroup $group): array
    {
        if (!Schema::hasTable('attendances')) {
            \Log::info('LATE_MAP: no attendances table');
            return [];
        }

        // helper untuk cek kolom
        $has = fn(string $c) => Schema::hasColumn('attendances', $c);

        // kandidat kolom tanggal
        $dateCol = collect(['date','work_date','attendance_date'])
            ->first($has);
        if (!$dateCol) {
            \Log::info('LATE_MAP: no date column');
            return [];
        }

        $startDate = $start->toDateString();
        $endDate   = $end->toDateString();

        // ambil employee id dalam group
        $empIds = self::employeeIdsInGroup($group->id)->all();
        if (empty($empIds)) {
            \Log::info('LATE_MAP: no employees in group', ['group'=>$group->id]);
            return [];
        }

        $map = array_fill_keys($empIds, 0);

        // ====== JALUR 1: kolom menit telat tersedia ======
        $lateCol = collect(['late_minutes','minutes_late','lateness','telat_minutes'])
            ->first($has);

        if ($lateCol) {
            $rows = DB::table('attendances')
                ->select('employee_id', DB::raw('COALESCE(SUM(COALESCE('.$lateCol.',0)),0) AS m'))
                ->whereBetween($dateCol, [$startDate, $endDate])
                ->whereIn('employee_id', $empIds)
                ->when($has('deleted_at'), fn($q) => $q->whereNull('deleted_at'))
                ->groupBy('employee_id')
                ->pluck('m','employee_id')
                ->toArray();

            foreach ($rows as $eid => $m) {
                $map[(int)$eid] = max(0, (int)$m);
            }
        }

        // ====== JALUR 2 (fallback): flag is_late × asumsi menit ======
        if (array_sum($map) === 0 && $has('is_late')) {
            $assume = 5; // asumsi per kejadian
            $rows = DB::table('attendances')
                ->select('employee_id', DB::raw('COUNT(*) AS c'))
                ->whereBetween($dateCol, [$startDate, $endDate])
                ->whereIn('employee_id', $empIds)
                ->where('is_late', 1)
                ->when($has('deleted_at'), fn($q) => $q->whereNull('deleted_at'))
                ->groupBy('employee_id')
                ->pluck('c','employee_id')
                ->toArray();

            foreach ($rows as $eid => $c) {
                $map[(int)$eid] = max($map[(int)$eid], (int)$c * $assume);
            }
        }

        
        // ====== JALUR 3 (di-upgrade): hitung dari check_in_time vs shift.start_time ======
        if (array_sum($map) === 0) {
            $rows = DB::table('attendances AS a')
                ->join('work_schedules AS ws', function ($join) {
                    $join->on('a.employee_id', '=', 'ws.employee_id')
                         ->on('a.date', '=', 'ws.work_date');
                })
                ->join('shifts AS s', 'ws.shift_id', '=', 's.id')
                ->whereBetween('a.date', [$startDate, $endDate])
                ->whereIn('a.employee_id', $empIds)
                ->whereNotNull('a.check_in_time')
                ->selectRaw("
                    a.employee_id,
                    SUM(
                        GREATEST(
                            TIMESTAMPDIFF(MINUTE, s.start_time, a.check_in_time),
                            0
                        )
                    ) AS m
                ")
                ->groupBy('a.employee_id')
                ->pluck('m', 'a.employee_id')
                ->toArray();

            foreach ($rows as $eid => $m) {
                $map[(int)$eid] = max($map[(int)$eid], (int)$m);
            }
        }

// Log ringkas biar mudah verifikasi
        \Log::info('LATE_MAP: summary', [
            'group'   => $group->id,
            'period'  => [$startDate, $endDate],
            'nonzero' => collect($map)->filter(fn($v)=>$v>0)->take(5),
        ]);

        return $map;
    }

}
