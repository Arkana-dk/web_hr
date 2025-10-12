<?php

namespace App\Domain\Payroll\Rules;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class LatePenaltyRule
{
    public function buildLines(array $ctx, Employee $employee, array $currentLines = []): array
    {
        $empId      = (int) ($employee->id ?? 0);
        $minutesRaw = (int) ($ctx['late_minutes'][$empId] ?? 0);
        
        // di awal buildLines(), setelah ambil $empId dan $minutesRaw
        \Log::debug('LATE_CTX', [
            'emp' => $empId,
            'minutes_raw' => $minutesRaw,
            'policy' => $ctx['late_policy'] ?? null,
        ]);


        // Hormati konfigurasi komponen di Pay Group
        $allowedCodes = collect($ctx['group_components'] ?? [])
            ->map(fn($gc) => strtoupper((string) optional($gc->component)->code))
            ->filter()->values()->all();
        if (!in_array('LATE_PENALTY', $allowedCodes, true) && !in_array('LATE', $allowedCodes, true)) {
            return []; // komponen tidak dipasang di pay group ini → jangan tulis baris
        }

        // Kebijakan
        $policy     = $ctx['late_policy'] ?? [];
        $grace      = (int) ($policy['grace_minutes'] ?? 5);
        $round      = (string) ($policy['rounding'] ?? 'none');   // none|ceil-15|ceil-30|ceil-60
        $cap        = (int) ($policy['period_cap_minutes'] ?? 0); // 0 = tanpa cap

        // grace → rounding → cap (pakai menit hasil akhir saja)
        $afterGrace = max(0, $minutesRaw - $grace);
        $rounded    = $this->applyRounding($afterGrace, $round);
        $effective  = $cap > 0 ? min($rounded, $cap) : $rounded;
        
        if ($effective <= 0) {
            \Log::debug('LATE_SKIP_ZERO', [
                'emp' => $empId,
                'raw' => $minutesRaw,
                'after_grace' => $afterGrace,
                'rounded' => $rounded,
                'effective' => $effective,
            ]);
            return [];
        }

        // Ambil komponen
        $comp = DB::table('pay_components')->whereIn('code', ['LATE_PENALTY','LATE'])->first();
        if (!$comp) return [];

        // Cari rate per menit: prefer rate komponen (per minute / per hour) → fallback BASIC/173/60
        [$perMinute, $basisMeta] = $this->resolveMinuteRateFromRates($ctx, $comp);
        if ($perMinute <= 0) {
            [$perMinute, $basisMeta] = $this->resolveMinuteByBasic173($employee, $currentLines);
        }
        if ($perMinute <= 0) return [];

        $amount = round($effective * $perMinute, 2);

        \Log::info('LatePenalty Debug', [
            'empId' => $empId,
            'minutesRaw' => $minutesRaw,
            'afterGrace' => $afterGrace,
            'rounded' => $rounded,
            'effective' => $effective,
            'perMinute' => $perMinute,
            'amount' => $amount,
            'comp' => $comp,
        ]);


        return [(object) [
            'component_code' => $comp->code ?? 'LATE_PENALTY',
            'componentCode'  => $comp->code ?? 'LATE_PENALTY',
            'component_type' => 'late_penalty',
            'componentType'  => 'late_penalty',
            'component_id'   => $comp->id ?? null,
            'name'           => $comp->name ?? 'Keterlambatan',
            'quantity'       => round($effective, 2),   // menit efektif
            'rate'           => round($perMinute, 6),   // IDR/menit
            'amount'         => $amount,
            'meta'           => array_merge($basisMeta, [
                'minutes_raw'         => $minutesRaw,
                'grace_minutes'       => $grace,
                'minutes_after_grace' => $afterGrace,
                'rounding'            => $round,
                'minutes_rounded'     => $rounded,
                'period_cap_minutes'  => $cap,
                'minutes_effective'   => $effective,
            ]),
            'side'           => 'deduction',
        ]];
    }

    public function apply($ctx, Employee $employee, $result): void
    {
        $current = (isset($result->lines) && is_array($result->lines)) ? $result->lines : [];
        $lines   = $this->buildLines($ctx, $employee, $current);
        if (!empty($lines)) $result->lines = array_merge($current, $lines);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function resolveMinuteRateFromRates(array $ctx, $component): array
    {
        $meta = ['source' => 'RATES'];
        $perMinute = 0.0;

        $componentId = (int) ($component->id ?? 0);
        if ($componentId <= 0) return [0.0, $meta];

        $buckets = $ctx['rates'][$componentId] ?? null;
        if (!$buckets) return [0.0, $meta];

        $groupId = $ctx['pay_group']->id ?? null;
        $rows = $groupId !== null && isset($buckets[$groupId]) ? $buckets[$groupId] : ($buckets['default'] ?? null);
        if (!$rows) return [0.0, $meta];

        $period = $ctx['period'] ?? ['start' => '1000-01-01', 'end' => '9999-12-31'];

        $picked = null;
        foreach ($rows as $r) {
            $rStart = $r->effective_start ?? '1000-01-01';
            $rEnd   = $r->effective_end   ?? '9999-12-31';
            if ($rStart <= $period['end'] && $rEnd >= $period['start']) {
                if ($picked === null || $rStart > $picked->effective_start) $picked = $r;
            }
        }
        if (!$picked) return [0.0, $meta];

        $unitRaw  = strtolower(trim((string) ($picked->unit ?? '')));
        $unitNorm = preg_replace('/\s*/', '', $unitRaw); // "idr / minute" -> "idr/minute"
        $rateVal  = (float) ($picked->rate ?? 0);        // ← PENTING: ini yang sempat hilang

        // per minute langsung
        if ($rateVal > 0 && (
                str_contains($unitNorm, 'idr/minute') ||
                preg_match('/\/min(ute)?\b/', $unitNorm) ||
                str_contains($unitRaw, 'per minute') ||
                str_contains($unitRaw, 'menit')
            )) {
            $meta['unit']   = $picked->unit;
            $meta['rate']   = $rateVal;
            $meta['schema'] = 'flat x minutes';
            return [$rateVal, $meta];
        }

        // per hour → konversi ke menit
        if ($rateVal > 0 && (
                str_contains($unitNorm, 'idr/hour') ||
                preg_match('/\/jam\b/', $unitNorm) ||
                str_contains($unitRaw, 'per hour')
            )) {
            $meta['unit']   = $picked->unit;
            $meta['rate']   = $rateVal;
            $meta['schema'] = 'converted hour→minute';
            return [($rateVal / 60.0), $meta];
        }

        return [0.0, $meta];
    }

    private function resolveMinuteByBasic173(Employee $employee, array $currentLines): array
    {
        $basic = 0.0;
        foreach ($currentLines as $ln) {
            $code = $ln->component_code ?? $ln->componentCode ?? null;
            if (strtoupper((string) $code) === 'BASIC') {
                $amt = $ln->amount ?? null;
                if (is_numeric($amt)) $basic = (float) $amt;
                break;
            }
        }
        if ($basic <= 0 && isset($employee->salary) && is_numeric($employee->salary)) {
            $basic = (float) $employee->salary;
        }
        $perMinute = $basic > 0 ? ($basic / 173.0 / 60.0) : 0.0;
        return [$perMinute, [
            'source' => 'BASIC/173/60',
            'basic'  => round($basic, 2),
            'note'   => 'standard minute = (BASIC / 173) / 60',
            'schema' => 'flat x minutes',
        ]];
    }

    private function applyRounding(float $minutes, string $round): float
    {
        if ($round === 'none' || $minutes <= 0) return $minutes;
        if (preg_match('/^ceil-(15|30|60)$/', $round, $m)) {
            $blk = (int) $m[1];
            return (float) (ceil($minutes / $blk) * $blk);
        }
        return $minutes;
    }
}
