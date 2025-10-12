<?php

namespace App\Domain\Payroll\Rules;

use App\Domain\Payroll\DTOs\CalcResult;
use App\Domain\Payroll\Rules\Contracts\PayrollRule;
use App\Models\{PayComponent, EmployeeComponent};

class ProrataBasicRule implements PayrollRule
{
    private static ?PayComponent $basicCompCache = null;

    // ⬇⬇⬇ ubah: param $ctx harus by-reference
    public function apply($ctx, $employee, CalcResult $result): void
    {
        $basic = self::$basicCompCache ??= PayComponent::where('code', 'BASIC')->first();
        \Log::debug('RULE_ENTER', ['rule' => __CLASS__, 'emp_id' => $employee->id]);

        if (!$basic) {
            $result->diagnostics = is_array($result->diagnostics ?? null) ? $result->diagnostics : [];
            $result->diagnostics[] = 'Komponen BASIC tidak ditemukan.';
            return;
        }

        $periodStart = $ctx['period']['start'] ?? now()->startOfMonth()->toDateString();
        $periodEnd   = $ctx['period']['end']   ?? now()->endOfMonth()->toDateString();

        $base  = $this->resolveBaseBasic($ctx, $employee, $basic);

        $attrs = $this->componentAttrs($basic);
        $mode  = strtolower((string)($attrs['proration'] ?? 'days'));

        $stat = $ctx['attendanceStats'][$employee->id] ?? [];
        $scheduledDays  = (int)($stat['scheduled_days']  ?? $stat['workdays'] ?? 0);
        $paidDays       = (int)($stat['paid_days']       ?? $stat['present_days'] ?? $scheduledDays);
        $scheduledHours = (float)($stat['scheduled_hours'] ?? 0);
        $paidHours      = (float)($stat['paid_hours']      ?? ($stat['worked_hours'] ?? 0));

        $qty = 1; $rate = $base; $amount = round($base, 2); $src = 'none';

        if ($mode === 'days') {
            $src = 'days';
            if ($scheduledDays > 0) {
                $rate   = $base / $scheduledDays;
                $qty    = max(0, min($paidDays, $scheduledDays));
                $amount = round($rate * $qty, 2);
            }
        } elseif ($mode === 'hours') {
            $src = 'hours';
            if ($scheduledHours > 0) {
                $rate   = $base / $scheduledHours;
                $qty    = max(0.0, min($paidHours, $scheduledHours));
                $amount = round($rate * $qty, 2);
            }
        }

        $result->addLine([
            'componentCode' => 'BASIC',
            'componentType' => 'earning',
            'side'          => method_exists($basic, 'getSideAttribute') ? $basic->side : 'earning',
            'name'          => $basic->name ?? 'Gaji Pokok',
            'quantity'      => $qty,
            'rate'          => $rate,
            'amount'        => $amount,
            'source'        => 'ProrataBasicRule/'.$src,
        ]);

        // ⬇⬇⬇ tambah: simpan BASIC prorata ke context untuk dipakai statutory
        $ctx['computed']['BASIC'] = $amount;

        \Log::debug('BASIC_TRACE', [
            'emp_id' => $employee->id,
            'base'   => $base,
            'mode'   => $mode,
            'qty'    => $qty,
            'rate'   => $rate,
            'amount' => $amount,
        ]);
    }


    /* ================= Helpers ================= */

    /**
     * Cari nilai BASIC untuk karyawan pada periode & pay group berjalan.
     * Urutan: override per-karyawan > rate pay group (unit amount) > default_amount komponen.
     */
    private function resolveBaseBasic(array $ctx, $employee, PayComponent $basicComp): float
    {
        $period = $ctx['period'] ?? ['start' => now()->toDateString(), 'end' => now()->toDateString()];
        $start  = $period['start'];
        $end    = $period['end'];

        // a) Override per-employee
        $override = EmployeeComponent::query()
            ->where('employee_id', $employee->id)
            ->where('pay_component_id', $basicComp->id)
            ->where(function ($q) use ($end) {
                $q->whereNull('effective_start')->orWhereDate('effective_start', '<=', $end);
            })
            ->where(function ($q) use ($start) {
                $q->whereNull('effective_end')->orWhereDate('effective_end', '>=', $start);
            })
            ->orderByDesc('effective_start')
            ->first();

        if ($override && $override->override_amount !== null) {
            return (float) $override->override_amount;
        }

        // b) Rate dari konteks (prefer milik pay group; fallback 'default')
        $groupId  = $ctx['pay_group']->id ?? null;
        $rateInfo = $this->pickRate($ctx, (int)$basicComp->id, $groupId, $period);
        if ($rateInfo && ($rateInfo[0] === 'amount' || $rateInfo[0] === null)) {
            return (float) $rateInfo[1];
        }

        // c) Fallback: default_amount komponen BASIC
        return (float) ($basicComp->default_amount ?? 0);
    }

    /**
     * Pilih rate yang overlap periode. Prefer milik pay group; fallback 'default'.
     * Return [unit, rate] atau null.
     */
    private function pickRate(array $ctx, int $componentId, ?int $groupId, array $period): ?array
    {
        $buckets = $ctx['rates'][$componentId] ?? null;
        if (!$buckets) return null;

        $candidates = [];
        if ($groupId !== null && isset($buckets[$groupId])) {
            $candidates = $buckets[$groupId];
        } elseif (isset($buckets['default'])) {
            $candidates = $buckets['default'];
        } else {
            return null;
        }

        $start = $period['start'];
        $end   = $period['end'];

        $picked = null;
        foreach ($candidates as $r) {
            $rStart = $r->effective_start ?? '1000-01-01';
            $rEnd   = $r->effective_end   ?? '9999-12-31';
            $overlap = ($rStart <= $end) && ($rEnd >= $start);
            if (!$overlap) continue;

            if ($picked === null || ($rStart > $picked->effective_start)) {
                $picked = $r;
            }
        }

        if (!$picked) return null;

        $unit = $picked->unit ?: 'amount'; // default ke amount
        $rate = (float) $picked->rate;
        return [$unit, $rate];
    }

    /**
     * Ambil konfigurasi komponen dari kolom JSON 'attributes' (atau 'config').
     */
    private function componentAttrs(PayComponent $comp): array
    {
        $raw = $comp->getAttribute('attributes') ?? $comp->getAttribute('config') ?? [];
        if (is_string($raw)) {
            $tmp = json_decode($raw, true);
            return is_array($tmp) ? $tmp : [];
        }
        return is_array($raw) ? $raw : [];
    }
}
