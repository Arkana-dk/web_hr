<?php

namespace App\Domain\Payroll\Services;

use App\Domain\Payroll\DTOs\CalcResult;
use App\Domain\Payroll\Rules\Contracts\PayrollRule;
use App\Domain\Payroll\Rules\ProrataBasicRule;
use App\Domain\Payroll\Rules\StatutoryContributionRule;
use App\Domain\Payroll\Rules\GenericComponentRule;
use App\Domain\Payroll\Rules\LatePenaltyRule;
use App\Domain\Payroll\Rules\Pph21Rule;


use App\Models\Employee;

class DefaultPayrollCalculator
{
    /** @var PayrollRule[] */
    protected array $rules;

    /**
     * @param PayrollRule[] $rules
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * Factory: pipeline default dengan urutan yang benar.
     */
    public static function makeDefault(): self
    {
        return new self([
            new ProrataBasicRule(),           // 1) Hitung BASIC prorata
            new StatutoryContributionRule(),  // 2) BPJS
            new GenericComponentRule(),       // 3) Komponen generic lainnya
            new LatePenaltyRule(),            // 4) Potongan keterlambatan
            new Pph21Rule(),                  // 5) PPh21 (gunakan gross/neto + config TER)
            
            // (tambahkan rules lain setelah ini, mis. OTRule, dsb.)
        ]);
    }

    /**
     * Jalankan pipeline rules untuk 1 karyawan.
     *
     * Mendukung dua gaya rule:
     * - Gaya baru: buildLines($ctx, Employee $employee, array $currentLines): array
     * - Gaya lama: apply(&$ctx, Employee $employee, CalcResult $result): void
     *
     * @param  mixed    $ctx
     * @param  Employee $employee
     * @return CalcResult
     */
    public function compute($ctx, Employee $employee): CalcResult
    {
        $result = new CalcResult();

        // Inisialisasi struktur dasar
        $result->lines = [];
        $result->diagnostics = [];

        // Jalankan semua rules (in-order)
        foreach ($this->rules as $rule) {
            $before = count($result->lines);

            try {
                if (method_exists($rule, 'buildLines')) {
                    // Gaya baru: merge lines
                    $newLines = $rule->buildLines($ctx, $employee, $result->lines);
                    if (is_array($newLines) && !empty($newLines)) {
                        foreach ($newLines as $ln) {
                            $result->lines[] = $this->toLineObject($ln);
                        }
                    }
                } elseif (method_exists($rule, 'apply')) {
                    // Gaya lama: rule memodifikasi $result
                    // NOTE: Kalau rule butuh memodifikasi $ctx (mis. ProrataBasicRule), pastikan
                    // signaturenya apply(&$ctx, ...). Caller tidak perlu & di sini.
                    $rule->apply($ctx, $employee, $result);
                } else {
                    $this->addDiag($result, sprintf('Rule %s tidak memiliki buildLines/apply.', get_class($rule)));
                }
            } catch (\Throwable $e) {
                // Jangan biarkan 1 rule menjatuhkan seluruh pipeline
                $this->addDiag($result, sprintf('Rule %s error: %s', get_class($rule), $e->getMessage()));
            }

            $after = count($result->lines);

            \Log::debug('RULE_TRACE', [
                'rule'      => get_class($rule),
                'emp_id'    => $employee->id,
                'added'     => $after - $before,
                'total_now' => $after,
            ]);
        }

        // Normalisasi & filter line anomali
        $result->lines = array_values(array_filter(
            array_map([$this, 'toLineObject'], $result->lines),
            fn($ln) => is_object($ln) && isset($ln->amount) && is_numeric($ln->amount)
        ));

        // Hitung gross/deductions/net jika belum/0
        $hasLines = count($result->lines) > 0;
        $grossSet = property_exists($result, 'gross');
        $dedSet   = property_exists($result, 'deductions');
        $bothZero = ((float)($result->gross ?? 0) === 0.0) && ((float)($result->deductions ?? 0) === 0.0);

        if ($hasLines && ((!$grossSet && !$dedSet) || $bothZero)) {
            [$gross, $ded] = $this->sumGrossAndDeductions($result->lines);
            $result->gross      = $gross;
            $result->deductions = $ded;
            $result->net        = $gross - $ded;
        } else {
            $g = (float)($result->gross ?? 0);
            $d = (float)($result->deductions ?? 0);
            if (!isset($result->net)) {
                $result->net = $g - $d;
            }
        }

        // Ringkasan log
        \Log::debug('RULE_SUMMARY', [
            'emp_id' => $employee->id,
            'gross'  => $result->gross ?? 0,
            'ded'    => $result->deductions ?? 0,
            'net'    => $result->net ?? 0,
            'lines'  => array_map(function($ln){
                return [
                    'code'   => $ln->component_code ?? $ln->componentCode ?? null,
                    'type'   => $ln->component_type ?? $ln->componentType ?? null,
                    'qty'    => $ln->quantity ?? null,
                    'rate'   => $ln->rate ?? null,
                    'amount' => $ln->amount ?? null,
                    'src'    => $ln->source ?? null,
                    'side'   => $ln->side ?? null,
                ];
            }, $result->lines ?? []),
            'diag'   => $result->diagnostics ?? [],
        ]);

        return $result;
    }

    /**
     * Hitung total gross & deductions dari lines.
     * Aturan:
     * - componentType: 'deduction' | 'tax' | 'statutory_deduction' → deductions (absolute)
     * - componentType: 'earning' | 'allowance' | 'ot' | 'overtime' | 'basic' → gross (positif)
     * - selain itu → fallback berdasarkan tanda amount (>=0 → gross; <0 → deductions)
     *
     * @param  array $lines
     * @return array [gross, deductions]
     */
    protected function sumGrossAndDeductions(array $lines): array
    {
        $gross = 0.0;
        $ded   = 0.0;

        foreach ($lines as $ln) {
            $ln = $this->toLineObject($ln);

            // Normalisasi side/type minimal
            $type = strtolower((string)($ln->componentType ?? $ln->component_type ?? ''));
            if (!$type && isset($ln->side)) {
                $type = ($ln->side === 'deduction') ? 'deduction' : 'earning';
            }

            $amount = (float)($ln->amount ?? 0);

            if (in_array($type, ['deduction','tax','statutory_deduction'], true)) {
                $ded += abs($amount);
            } elseif (in_array($type, ['earning','allowance','ot','overtime','basic'], true)) {
                $gross += max(0, $amount);
            } else {
                // fallback by sign
                if ($amount >= 0) {
                    $gross += $amount;
                } else {
                    $ded += abs($amount);
                }
            }
        }

        return [round($gross, 2), round($ded, 2)];
    }

    protected function addDiag(CalcResult $res, string $msg): void
    {
        if (!isset($res->diagnostics) || !is_array($res->diagnostics)) {
            $res->diagnostics = [];
        }
        $res->diagnostics[] = $msg;
    }

    /**
     * Pastikan line berbentuk object & normalisasi properti umum.
     */
    private function toLineObject($ln): object
    {
        if (is_array($ln)) $ln = (object)$ln;

        // Aliases untuk kompat berbagai rule
        if (!isset($ln->componentCode) && isset($ln->component_code)) {
            $ln->componentCode = $ln->component_code;
        }
        if (!isset($ln->componentType) && isset($ln->component_type)) {
            $ln->componentType = $ln->component_type;
        }
        if (!isset($ln->name) && isset($ln->component_name)) {
            $ln->name = $ln->component_name;
        }

        // Default side dari type bila side kosong
        if (!isset($ln->side) && isset($ln->componentType)) {
            $ln->side = strtolower($ln->componentType) === 'deduction' ? 'deduction' : 'earning';
        }

        return $ln;
    }
}
