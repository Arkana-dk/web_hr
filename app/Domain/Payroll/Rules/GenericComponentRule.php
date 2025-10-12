<?php

namespace App\Domain\Payroll\Rules;

use App\Domain\Payroll\DTOs\CalcResult;
use App\Domain\Payroll\Rules\Contracts\PayrollRule;
use App\Models\{PayComponent, EmployeeComponent};

class GenericComponentRule implements PayrollRule
{
    /** Cache override untuk menghindari N+1 */
    private array $overrideCache = [];

   public function apply($ctx, $employee, CalcResult $result): void
{
    // kalau context belum bawa mapping, tidak usah apa-apa
    if (empty($ctx['group_components']) || !is_iterable($ctx['group_components'])) {
        return;
    }

    // daftar skip (static agar tidak di-build ulang)
    static $STATUTORY = [
        'BPJSKES_EE','JHT_EE','JP_EE','PPH21_EE',
        'BPJSKES_ER','JHT_ER','JP_ER','JKK_ER','JKM_ER',
    ];
    static $SKIP_CODES = ['LATE_PENALTY', 'LATE', 'OVERTIME'];

    foreach ($ctx['group_components'] as $gc) {
        $comp = $gc->component ?? null;
        if (!$comp || !($gc->active ?? false)) {
            continue;
        }

        $code   = (string)($comp->code ?? '');
        $codeUp = strtoupper($code);

        // ==== komponen yang ditangani rule lain / tidak boleh di generic ====
        if ($codeUp === 'BASIC') continue;                               // ProrataBasicRule
        if (in_array($codeUp, $SKIP_CODES, true)) continue;              // LATE/OVERTIME → rule lain
        if (strpos($codeUp, 'OT_') === 0) continue;                      // prefix OT_*
        if (in_array($codeUp, $STATUTORY, true)) continue;               // statutory
        // ===================================================================

        // side/tipe baris (fallback earning)
        $kind = strtolower((string)($comp->kind ?? ''));
        $type = $kind === 'deduction' ? 'deduction' : ($kind === 'info' ? 'info' : 'earning');

        // normalisasi calc type (attributes / kolom)
        $attrs = $this->attrs($comp);
        $calc  = strtolower(trim((string)($attrs['calc_type'] ?? $comp->calc_type ?? 'fixed')));

        // statistik attendance
        $stat    = $ctx['attendanceStats'][$employee->id] ?? [];
        $present = (int)($stat['present_days'] ?? 0);
        $paid    = (int)($stat['paid_days'] ?? 0);

        // override per karyawan (cached)
        $override = $this->findOverride($ctx, (int)$employee->id, (int)($comp->id ?? 0));

        // 1) override_amount → langsung pakai
        if ($override && $override->override_amount !== null) {
            $amount = (float)$override->override_amount;
            if ($amount != 0.0 || (bool)($gc->mandatory ?? false) || $type === 'info') {
                $result->addLine($this->line(
                    $codeUp,
                    $type,
                    (string)($comp->name ?? $codeUp),
                    1,
                    null,
                    $amount,
                    'GenericComponentRule/override_amount'
                ));
            }
            continue;
        }

        // 2) hitung berdasarkan calc type
        $qty = 1;
        $unitRate = null;
        $amount = 0.0;

        switch ($calc) {
            case 'daily': {
                $rateInfo = $this->pickRate($ctx, (int)$comp->id, $ctx['pay_group']->id ?? null, $ctx['period']);
                $rate     = $override && $override->override_rate !== null
                            ? (float)$override->override_rate
                            : (float)($rateInfo[1] ?? ($comp->default_amount ?? 0));
                $qty      = $present;
                $unitRate = $rate;
                $amount   = $qty * $rate;
                break;
            }

            case 'percent':
            case 'percent_of_basic': {
                // cari persen (rasio)
                $percentDec = null;

                if ($override && $override->override_percent !== null) {
                    $percentDec = (float)$override->override_percent;
                    if ($percentDec > 1) $percentDec /= 100.0;   // 12 → 0.12
                } else {
                    $rateInfo = $this->pickRate($ctx, (int)$comp->id, $ctx['pay_group']->id ?? null, $ctx['period']);
                    if ($rateInfo) {
                        [$u, $r] = $rateInfo;
                        if ($u === 'percent')   $percentDec = ((float)$r) / 100.0;
                        elseif ($u === 'ratio') $percentDec = (float)$r;
                    }
                    if ($percentDec === null && $comp->default_amount !== null) {
                        $percentDec = ((float)$comp->default_amount) / 100.0;
                    }
                }
                if ($percentDec === null) $percentDec = 0.0;

                // basis (default BASIC) + cap
                $basisCode = $this->basisCodeFromAttrs($attrs, 'BASIC');
                $basis     = $this->resolveBasisAmount($ctx, $employee, $basisCode);
                $cap       = $this->capFromAttrs($attrs);

                $unitRate = $percentDec;          // simpan rasio
                $amount   = $basis * $percentDec;
                if ($cap !== null) $amount = min($amount, $cap);
                $qty = 1;
                break;
            }

            case 'hourly':
                // jumlah jam tidak diketahui Generic → biar rule OT
                continue 2;

            case 'formula': {
                $rateInfo = $this->pickRate($ctx, (int)$comp->id, $ctx['pay_group']->id ?? null, $ctx['period']);
                $rate     = $override && $override->override_rate !== null
                            ? (float)$override->override_rate
                            : (float)($rateInfo[1] ?? 0);

                $vars = [
                    'BASIC'        => $this->resolveBasisAmount($ctx, $employee, 'BASIC'),
                    'PRESENT_DAYS' => $present,
                    'PAID_DAYS'    => $paid,
                    'RATE'         => $rate,
                ];
                $expr = $override && !empty($override->override_formula)
                        ? $override->override_formula
                        : ($attrs['formula'] ?? null);

                $amount   = $this->safeFormula($expr, $vars);
                $unitRate = $rate;

                if ($amount === null) {
                    $result->warn("Formula untuk {$codeUp} tidak valid; baris dilewati.");
                    continue 2;
                }
                break;
            }

            default: { // fixed (atau tak dikenal → fixed)
                $rateInfo = $this->pickRate($ctx, (int)$comp->id, $ctx['pay_group']->id ?? null, $ctx['period']);
                $rate     = $override && $override->override_rate !== null
                            ? (float)$override->override_rate
                            : (float)($rateInfo[1] ?? ($comp->default_amount ?? 0));
                $unitRate = $rate;
                $amount   = $rate;
                break;
            }
        }

        // 3) tulis baris jika perlu
        if ($amount == 0.0 && !(bool)($gc->mandatory ?? false) && $type !== 'info') {
            continue;
        }

        $result->addLine($this->line(
            $codeUp,
            $type,
            (string)($comp->name ?? $codeUp),
            $qty,
            $unitRate,
            $amount,
            'GenericComponentRule/'.($calc ?: 'fixed')
        ));
    }
}


    /* ================= Helpers ================= */

    private function attrs(PayComponent $comp): array
    {
        $attrs = $comp->attributes ?? [];
        if (is_string($attrs)) {
            $tmp = json_decode($attrs, true);
            if (json_last_error() === JSON_ERROR_NONE) $attrs = $tmp;
        }
        if (!isset($attrs['calc_type']) && !empty($comp->calc_type)) {
            $attrs['calc_type'] = $comp->calc_type;
        }
        return is_array($attrs) ? $attrs : [];
    }

    private function findOverride(array $ctx, int $employeeId, int $componentId): ?EmployeeComponent
    {
        $key = $employeeId.'-'.$componentId;
        if (array_key_exists($key, $this->overrideCache)) {
            return $this->overrideCache[$key];
        }

        $ovr = EmployeeComponent::query()
            ->where('employee_id', $employeeId)
            ->where('pay_component_id', $componentId)
            ->where(function ($q) use ($ctx) {
                $q->whereNull('effective_start')->orWhereDate('effective_start', '<=', $ctx['period']['end']);
            })
            ->where(function ($q) use ($ctx) {
                $q->whereNull('effective_end')->orWhereDate('effective_end', '>=', $ctx['period']['start']);
            })
            ->orderByDesc('effective_start')
            ->first();

        return $this->overrideCache[$key] = $ovr;
    }

    /**
     * Pilih rate overlap periode. Prefer rate milik pay group; fallback 'default'.
     * Return [unitCanonical, rate] atau null.
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

        $start  = $period['start'];
        $end    = $period['end'];
        $picked = null;

        foreach ($candidates as $r) {
            $rStart  = $r->effective_start ?? '1000-01-01';
            $rEnd    = $r->effective_end   ?? '9999-12-31';
            if ($rStart <= $end && $rEnd >= $start) {
                if ($picked === null || $rStart > $picked->effective_start) $picked = $r;
            }
        }
        if (!$picked) return null;

        $rawUnit = strtolower((string) ($picked->unit ?? ''));
        $map = [
            '%'          => 'percent',
            'percentage' => 'percent',
            'persen'     => 'percent',
            'ratio'      => 'ratio',
            'rasio'      => 'ratio',
            'amount'     => 'amount',
            'nominal'    => 'amount',
            'custom'     => 'custom',
        ];
        $unit = $map[$rawUnit] ?? ($rawUnit ?: 'amount');

        return [$unit, (float) $picked->rate];
    }

    private function basisCodeFromAttrs(array $attrs, string $fallback = 'BASIC'): string
    {
        return (string) ($attrs['basis'] ?? $fallback);
    }

    private function resolveBasisAmount(array $ctx, $employee, string $componentCode): float
    {
        // 0) nilai yang sudah dihitung rule lain (mis. BASIC dari ProrataBasicRule)
        if (!empty($ctx['computed'][$componentCode])) {
            return (float) $ctx['computed'][$componentCode];
        }

        // 1) override basis per-employee
        $override = EmployeeComponent::query()
            ->where('employee_id', $employee->id)
            ->whereHas('component', fn ($q) => $q->where('code', $componentCode))
            ->where(function ($q) use ($ctx) {
                $q->whereNull('effective_start')->orWhereDate('effective_start', '<=', $ctx['period']['end']);
            })
            ->where(function ($q) use ($ctx) {
                $q->whereNull('effective_end')->orWhereDate('effective_end', '>=', $ctx['period']['start']);
            })
            ->orderByDesc('effective_start')
            ->first();

        if ($override && $override->override_amount !== null) {
            return (float) $override->override_amount;
        }

        // 2) dari mapping group
        $map = collect($ctx['group_components'])->first(fn ($gc) => optional($gc->component)->code === $componentCode);
        if ($map && $map->component && $map->component->default_amount !== null) {
            return (float) $map->component->default_amount;
        }

        // 3) default dari komponen
        $baseComp = PayComponent::where('code', $componentCode)->first();
        return (float) ($baseComp->default_amount ?? 0);
    }

    private function capFromAttrs(array $attrs): ?float
    {
        return (isset($attrs['cap']) && is_numeric($attrs['cap'])) ? (float)$attrs['cap'] : null;
    }

    private function line(string $code, string $type, ?string $name, int $qty, $rate, float $amount, string $source): array
    {
        return [
            'componentCode' => $code,
            'componentType' => $type, // earning|deduction|info
            'side'          => ($type === 'deduction') ? 'deduction' : 'earning',
            'name'          => $name ?? $code,
            'quantity'      => $qty,
            'rate'          => $rate,
            'amount'        => $amount,
            'source'        => $source,
        ];
    }

    /**
     * Evaluasi formula sederhana yang sudah disanitasi.
     * Return float|null (null jika tak valid/expr kosong).
     */
    private function safeFormula(?string $expr, array $vars): ?float
    {
        if (!$expr) return null;

        $safe = $expr;
        foreach ($vars as $k => $v) {
            $safe = preg_replace('/\b'.preg_quote($k, '/').'\b/', (string) (float) $v, $safe);
        }

        // Izinkan hanya angka, spasi, dan +-*/().
        if (preg_match('/[^0-9\.\+\-\*\/\(\)\s]/', $safe)) {
            return null;
        }

        try {
            /** @noinspection PhpExpressionResultUnusedInspection */
            // @phpstan-ignore-next-line
            $val = @eval('return '.$safe.';');
            return is_numeric($val) ? (float) $val : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
