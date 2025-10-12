<?php

namespace App\Domain\Payroll\Rules;

use App\Domain\Payroll\DTOs\CalcResult;
use App\Domain\Payroll\Rules\Contracts\PayrollRule;
use App\Models\{PayComponent, EmployeeComponent};
use DateTime;

class StatutoryContributionRule implements PayrollRule
{
    /** komponen iuran porsi karyawan (EE) yang dihitung di rule ini */
    private array $statutoryEeCodes = ['BPJSKES_EE','JHT_EE','JP_EE','PPH21_EE'];

    public function apply($ctx, $employee, CalcResult $result): void
    {
        if (empty($ctx['group_components'])) return;

        foreach ($ctx['group_components'] as $gc) {
            $comp = $gc->component ?? null;
            if (!$comp || !($gc->active ?? false)) continue;

            $code = strtoupper((string)($comp->code ?? ''));
            if (!in_array($code, $this->statutoryEeCodes, true)) continue;

            // --- PPh21 dihitung khusus ---
            if ($code === 'PPH21_EE') {
                $calc = $this->calculatePPh21($ctx, $employee, $result);
            } else {
                // --- selain PPh21 gunakan mekanisme statutory (rate x basis / nominal / override) ---
                $calc = $this->resolveStatutoryAmount($ctx, $employee, $comp, $code, $result);
            }

            // Back-compat: jika masih numerik, bungkus ke {amount, qty, rate}
            if (is_numeric($calc)) {
                $calc = ['amount' => (float)$calc, 'quantity' => 1.0, 'rate' => (float)$calc];
            }

            $amount   = (float) round($calc['amount'] ?? 0.0, 2);
            $quantity = isset($calc['quantity']) ? (float)$calc['quantity'] : 1.0;
            $rate     = $calc['rate'] ?? null;

            if (abs($amount) < 0.000001) continue;

            if ($rate === null) {
                $qtySafe = ($quantity === 0.0) ? 1.0 : $quantity;
                $rate = round($amount / $qtySafe, 2);
            }

            $result->addLine([
                'componentCode' => $code,
                'componentType' => 'deduction',
                'side'          => 'deduction',
                'name'          => $comp->name ?? $code,
                'quantity'      => $quantity,
                'rate'          => $rate,
                'amount'        => $amount * -1, // potongan (negatif)
                'source'        => 'StatutoryContributionRule/auto',
                'meta'          => $calc['meta'] ?? [],
            ]);
        }
    }

    /**
     * Hitung amount untuk 1 komponen statutory EE (selain PPh21) dan kembalikan {amount, quantity, rate, meta}.
     * - percent/ratio: amount = basis_efektif * ratio, quantity = ratio, rate = basis_efektif (untuk UI)
     * - amount/nominal: amount = rate, quantity = 1, rate = amount
     * - override per-employee (jika ada) mengalahkan lainnya
     */
    private function resolveStatutoryAmount(array $ctx, $employee, PayComponent $comp, string $code, CalcResult $result)
    {
        \Log::debug('Statutory', [
            'comp'  => $comp->code,
            'rates' => $ctx['rates'][$comp->id] ?? null
        ]);

        // 0) Override per-employee (prioritas tertinggi)
            $override = EmployeeComponent::query()
            ->where('employee_id', $employee->id)
            ->where('pay_component_id', $comp->id)  // ganti ini
            ->where(function ($q) use ($ctx) {
                $q->whereNull('effective_start')
                ->orWhereDate('effective_start', '<=', $ctx['period']['end'] ?? date('Y-m-d'));
            })
            ->where(function ($q) use ($ctx) {
                $q->whereNull('effective_end')
                ->orWhereDate('effective_end', '>=', $ctx['period']['start'] ?? date('Y-m-d'));
            })
            ->orderByDesc('effective_start')
            ->first();


        if ($override && $override->override_amount !== null) {
            $amt = (float)$override->override_amount;
            return ['amount' => $amt, 'quantity' => 1.0, 'rate' => $amt, 'meta' => ['source' => 'override']];
        }

        // 1) Rate dari pay group / default
        $rateInfo = $this->pickRate($ctx, (int)$comp->id, $ctx['pay_group']->id ?? null, $ctx['period'] ?? ['start'=>date('Y-m-d'),'end'=>date('Y-m-d')]);
        if ($rateInfo) {
            $unit    = $rateInfo['unit'];
            $rateVal = (float)$rateInfo['rate'];
            $meta    = $rateInfo['meta'] ?? [];

            if ($unit === 'percent' || $unit === 'ratio') {
                // basis dari meta/attributes → default 'BASIC'
                $basisCodeMeta = $meta['basis'] ?? null;
                $basisCode     = $basisCodeMeta ?: ($this->basisCodeFrom($comp) ?: 'BASIC');

                $basisCodeNorm = strtoupper(match (strtolower($basisCode)) {
                    'bpjs_base','bpjs','bpjskes','bpjstk' => 'BASIC', // alias umum
                    default => $basisCode
                });

                // basis PRORATA: jika 'BASIC' ambil dari result (sudah dihitung prorata)
                $basicFromResult = $this->getBasicAmountFromResult($result);
                $basisProrated = ($basisCodeNorm === 'BASIC' && $basicFromResult !== null)
                    ? $basicFromResult
                    : $this->resolveBasisAmount($ctx, $employee, $basisCodeNorm);

                // CAP dari meta rate
                $cap         = (isset($meta['cap']) && is_numeric($meta['cap'])) ? (float)$meta['cap'] : null;
                $capProrated = array_key_exists('cap_prorated', $meta) ? (bool)$meta['cap_prorated'] : false;

                // Hitung basis efektif sesuai cap_prorated
                if ($cap !== null && $cap > 0) {
                    if ($capProrated) {
                        $basisEffective = min($basisProrated, $cap);
                    } else {
                        $monthlyBase    = $this->resolveMonthlyBasis($ctx, $employee, $basisCodeNorm);
                        $ratio          = $this->prorataRatio($monthlyBase, $basisProrated);
                        $monthlyCapped  = min($monthlyBase, $cap);
                        $basisEffective = $monthlyCapped * $ratio;
                    }
                } else {
                    $basisEffective = $basisProrated;
                }

                $ratio  = ($unit === 'percent') ? $this->toRatio($rateVal) : $rateVal;
                $amount = (float)($basisEffective * $ratio);

                return [
                    'amount'   => $amount,
                    'quantity' => (float)$ratio,
                    'rate'     => (float)$basisEffective,
                    'meta'     => ['basis_code' => $basisCodeNorm, 'basis_effective' => $basisEffective]
                ];
            }

            // nominal / amount
            $amt = (float)$rateVal;
            return ['amount' => $amt, 'quantity' => 1.0, 'rate' => $amt, 'meta' => ['basis_code' => null]];
        }

        // 2) Fallback: default_amount pada komponen
        $amt = (float)($comp->default_amount ?? 0);
        return ['amount' => $amt, 'quantity' => 1.0, 'rate' => $amt, 'meta' => ['source' => 'component.default']];
    }

    /** Ambil nominal BASIC yang sudah dihitung di result (prorata) */
    private function getBasicAmountFromResult(CalcResult $result): ?float
    {
        if (empty($result->lines)) return null;
        foreach ($result->lines as $ln) {
            $code = $ln->componentCode ?? $ln->component_code ?? null;
            if (strtoupper((string)$code) === 'BASIC') {
                $amt = $ln->amount ?? null;
                return is_numeric($amt) ? (float)$amt : null;
            }
        }
        return null;
    }

    /** basis bulanan (non-prorata) untuk suatu code (mis. BASIC) */
    private function resolveMonthlyBasis(array $ctx, $employee, string $componentCode): float
    {
        $override = EmployeeComponent::query()
            ->where('employee_id', $employee->id)
            ->whereHas('component', fn ($q) => $q->where('code', $componentCode))
            ->orderByDesc('effective_start')
            ->first();

        if ($override && $override->override_amount !== null) {
            return (float)$override->override_amount;
        }

        $map = collect($ctx['group_components'])->first(function ($gc) use ($componentCode) {
            return optional($gc->component)->code === $componentCode;
        });
        if ($map && $map->component && $map->component->default_amount !== null) {
            return (float)$map->component->default_amount;
        }

        $basisComp = PayComponent::where('code', $componentCode)->first();
        return (float) ($basisComp->default_amount ?? 0);
    }

    /** konversi nilai prorata terhadap bulanan → [0..1] */
    private function prorataRatio(float $monthly, ?float $prorated): float
    {
        if ($monthly <= 0 || $prorated === null) return 1.0;
        return max(0.0, min(1.0, $prorated / $monthly));
    }

    /**
     * Pilih rate yang overlap periode. Prefer milik pay group; fallback 'default'.
     * Return ['unit','rate','meta'] atau null.
     */
    private function pickRate(array $ctx, int $componentId, ?int $groupId, array $period): ?array
    {
        $buckets = $ctx['rates'][$componentId] ?? null;
        if (!$buckets) return null;

        $candidates = $groupId !== null && isset($buckets[$groupId])
            ? $buckets[$groupId]
            : ($buckets['default'] ?? null);
        if (!$candidates) return null;

        $start = $period['start']; $end = $period['end']; $picked = null;
        foreach ($candidates as $r) {
            $rs = $r->effective_start ?? '1000-01-01';
            $re = $r->effective_end   ?? '9999-12-31';
            if ($rs <= $end && $re >= $start) {
                if ($picked === null || ($rs > $picked->effective_start)) $picked = $r;
            }
        }
        if (!$picked) return null;

        // normalisasi unit
        $rawUnit = strtolower((string)($picked->unit ?? ''));
        $map = [
            '%' => 'percent','percentage' => 'percent','persen' => 'percent',
            'ratio' => 'ratio','rasio' => 'ratio',
            'amount' => 'amount','nominal' => 'amount',
            'custom' => 'custom'
        ];
        $unit = $map[$rawUnit] ?? ($rawUnit ?: 'amount');
        $rate = (float)($picked->rate ?? 0);

        // meta (basis/cap/cap_prorated) dari rate
        $meta = $picked->meta ?? [];
        if (is_string($meta)) {
            $dec = json_decode($meta, true);
            if (json_last_error() === JSON_ERROR_NONE) $meta = $dec;
        }
        if (!is_array($meta)) $meta = [];

        return ['unit'=>$unit,'rate'=>$rate,'meta'=>$meta];
    }

    /** Ambil basis code dari attributes komponen (default 'BASIC') */
    private function basisCodeFrom(PayComponent $comp): string
    {
        $attrs = $comp->attributes ?? [];
        if (is_string($attrs)) {
            $decoded = json_decode($attrs, true);
            if (json_last_error() === JSON_ERROR_NONE) $attrs = $decoded;
        }
        return (string) ($attrs['basis'] ?? 'BASIC');
    }

    /** ubah persen→rasio aman: 2 -> 0.02; jika sudah 0.02 tetap 0.02 */
    private function toRatio(float $rate): float
    {
        if ($rate <= 0) return 0.0;
        return $rate >= 1.0 ? $rate / 100.0 : $rate;
    }

    // =========================
    // ===  PPh21 (TER)     ===
    // =========================

    /**
     * Hitung PPh21 bulanan dengan TER; opsional true-up di masa terakhir.
     * Mengembalikan ['amount','quantity','rate','meta'] (amount positif; dipotong negatif saat addLine).
     */
    private function calculatePPh21(array $ctx, $employee, CalcResult $result): array
    {
        // 1) Bruto taxable (sesuaikan daftar komponen taxable di sistemmu)
        $gross = (float)$this->sumComponents($result, [
            'BASIC','ALLOWANCE','POSITION','OVERTIME','OTHER_TAXABLE_ALLOW'
        ]);


        // 2) Biaya jabatan 5% max 500rb
        $biayaJabatan = min(0.05 * $gross, 500_000.0);

        // 3) Iuran pekerja yang jadi pengurang
        $employeeContrib = (float)$this->sumComponents($result, [
            'JHT_EE','JP_EE','BPJSKES_EE'
        ]);

        // 4) Neto bulanan
        $netoBulanan = max(0.0, $gross - $biayaJabatan - $employeeContrib);

        // 5) Status PTKP
        $status = (string)($employee->tax_status ?? 'TK/0');

        // 6) Ambil TER
        $ter = (float)$this->getTER($ctx, $status, $netoBulanan);

        // 7) PPh21 masa (bulanan) via TER
        $pph21Masa = (float)round($ter * $netoBulanan);

        $meta = [
            'basis_gross'      => $gross,
            'biaya_jabatan'    => $biayaJabatan,
            'employee_contrib' => $employeeContrib,
            'basis_neto'       => $netoBulanan,
            'status_ptkp'      => $status,
            'ter'              => $ter,
        ];

        // 8) True-up tahunan (opsional)
        $enableTrueUp = (bool)($ctx['pph21']['enable_true_up'] ?? false);
        $periodDate   = ($ctx['period_date'] ?? null) instanceof DateTime
            ? $ctx['period_date'] : new DateTime($ctx['period']['end'] ?? 'now');

        if ($enableTrueUp && $this->isLastTaxPeriod($periodDate, $employee)) {
            $ytdNeto  = (float)($ctx['pph21']['ytd_neto']  ?? 0.0);
            $ytdPPh21 = (float)($ctx['pph21']['ytd_pph21'] ?? 0.0);

            // Proyeksi neto setahun sederhana
            $bulan = (int)$periodDate->format('n');
            $proyeksiNeto = (($ytdNeto + $netoBulanan) / max(1, $bulan)) * 12.0;

            $ptkp = (float)$this->getPTKP($ctx, $status);
            $pkp  = max(0.0, $proyeksiNeto - $ptkp);

            $pph21Tahunan = (float)$this->annualProgressiveTax($pkp);
            $trueUpAdj    = max(0.0, $pph21Tahunan - $ytdPPh21 - $pph21Masa);

            $pph21Masa += (float)round($trueUpAdj);

            $meta['true_up_adjustment'] = $trueUpAdj;
            $meta['ptkp']               = $ptkp;
            $meta['pkp']                = $pkp;
            $meta['pph21_tahunan']      = $pph21Tahunan;
        }

        return [
            'amount'   => $pph21Masa,
            'quantity' => 1.0,
            'rate'     => $ter,
            'meta'     => $meta,
        ];
    }

    private function sumComponents(CalcResult $result, array $codes): float
    {
        $sum = 0.0;
        foreach ($codes as $c) {
            $sum += (float)$result->getComponentTotal($c);
        }
        return $sum;
    }

    private function isLastTaxPeriod(DateTime $periodDate, $employee): bool
    {
        $isDecember = ((int)$periodDate->format('n') === 12);
        $isTerminated = method_exists($employee, 'isTerminatedThisMonth')
            ? (bool)$employee->isTerminatedThisMonth()
            : false;
        return $isDecember || $isTerminated;
    }

    /** Tarif progresif tahunan Pasal 17 (UU HPP, 5–35%) */
    private function annualProgressiveTax(float $pkp): float
    {
        $layers = [
            [60_000_000.0,   0.05],
            [250_000_000.0,  0.15],
            [500_000_000.0,  0.25],
            [5_000_000_000.0,0.30],
            [INF,            0.35],
        ];
        $prev = 0.0; $tax = 0.0;
        foreach ($layers as [$cap, $rate]) {
            $portion = max(0.0, min($pkp, $cap) - $prev);
            if ($portion <= 0.0) break;
            $tax += $portion * $rate;
            $prev = $cap;
        }
        return round($tax);
    }
    
        /**
     * Ambil TER sesuai status & neto bulanan.
     * - Prioritas: ctx['config']->getTER() jika ada.
     * - Fallback: config('pph21.ter_table').
     */
    private function getTER(array $ctx, string $status, float $netoBulanan): float
    {
        // 1) Kalau ada config object di context
        if (isset($ctx['config']) && is_object($ctx['config']) && method_exists($ctx['config'], 'getTER')) {
            $val = (float)$ctx['config']->getTER($status, $netoBulanan);
            if ($val > 0) {
                return $val;
            }
        }

        // 2) Default ke config file
        return $this->getTerRate($netoBulanan, $status);
    }

    /**
     * Lookup TER rate dari config/pph21.php
     */
    private function getTerRate(float $netoBulanan, string $status): float
    {
        $table = config('pph21.ter_table');

        if (!is_array($table) || !isset($table[$status])) {
            \Log::warning("TER table missing or undefined for status: {$status}");
            return 0.0;
        }

        foreach ($table[$status] as $row) {
            $max = $row['max'] ?? null;
            $rate = $row['rate'] ?? 0.0;

            if ($max === null || $netoBulanan <= $max) {
                return (float)$rate;
            }
        }

        // fallback terakhir kalau loop gagal
        $last = end($table[$status]);
        return (float)($last['rate'] ?? 0.0);
    }


        /**
     * Dapatkan PTKP tahunan berdasarkan status.
     * - Prioritas: ctx['config']->getPTKP() jika tersedia.
     * - Fallback: nilai default sesuai PMK 101/2016.
     */
    private function getPTKP(array $ctx, string $status): float
    {
        // 1) Kalau ada config object di context
        if (isset($ctx['config']) && is_object($ctx['config']) && method_exists($ctx['config'], 'getPTKP')) {
            $val = (float)$ctx['config']->getPTKP($status);
            if ($val > 0) {
                return $val;
            }
        }

        // 2) Fallback ke PMK 101/2016
        $base = 54_000_000.0; // TK/0
        $mar  =  4_500_000.0; // tambahan kawin
        $dep  =  4_500_000.0; // tambahan per tanggungan (maks 3)

        $map = [
            'TK/0' => $base,
            'K/0'  => $base + $mar,
            'K/1'  => $base + $mar + 1 * $dep,
            'K/2'  => $base + $mar + 2 * $dep,
            'K/3'  => $base + $mar + 3 * $dep,
        ];

        if (!isset($map[$status])) {
            \Log::warning("PTKP fallback used for unknown status: {$status}");
        }

        return $map[$status] ?? $base;
    }

}
