<?php

// app/Domain/Payroll/DTOs/CalcResult.php
namespace App\Domain\Payroll\DTOs;

class CalcResult
{
    public float $gross = 0.0;
    public float $deductions = 0.0;
    public float $net = 0.0;

    /** @var array<int,object> */
    public array $lines = [];

    /** @var array<int,string> */
    public array $diagnostics = [];

    /**
     * Tambah 1 line. Boleh array atau object.
     * Expected fields: componentCode, componentType (earning|deduction|tax|info), side, name, quantity, rate, amount, source
     */
    public function addLine($line): void
    {
        // 1) Normalisasi ke object
        if (is_array($line)) {
            $o = new \stdClass();
            $o->componentCode = $line['componentCode'] ?? ($line['component_code'] ?? null);
            $o->componentType = $line['componentType'] ?? ($line['component_type'] ?? null);
            $o->name          = $line['name'] ?? ($line['label'] ?? null);
            $o->quantity      = $line['quantity'] ?? ($line['qty'] ?? 1);
            $o->rate          = $line['rate'] ?? null;
            $o->amount        = $line['amount'] ?? 0;
            $o->source        = $line['source'] ?? 'calc';
            // NEW: normalize side (prefer explicit)
            $o->side          = $line['side'] ?? null;
            $line = $o;
        } elseif (!is_object($line)) {
            return;
        }

        // 2) Minimal validasi
        if (!isset($line->amount) || !is_numeric($line->amount)) return;

        // 3) Normalisasi type & side
        $type = strtolower((string)($line->componentType ?? ''));
        // Jika 'side' belum ada, turunkan dari type/kind
        if (!isset($line->side) || !$line->side) {
            if (in_array($type, ['deduction','tax','statutory','statutory_deduction'], true)) {
                $line->side = 'deduction';
            } elseif ($type === 'info') {
                // Baris info tidak mempengaruhi net, tapi aman di-mark earning untuk tampilan
                $line->side = 'earning';
            } else {
                $line->side = 'earning';
            }
        }
        $line->side = strtolower((string)$line->side);

        // 4) Simpan line
        $this->lines[] = $line;
        // (Opsional) hitung incremental — kalau kamu ingin totals langsung ter-update:
        // $this->accumulate($line);
    }

    public function addLines(array $lines): void
    {
        foreach ($lines as $ln) $this->addLine($ln);
    }

    /** Hitung ulang totals dari lines (panggil di akhir kalkulasi atau sebelum persist). */
    public function finalizeTotals(): void
    {
        $gross = 0.0; $ded = 0.0;
        foreach ($this->lines as $ln) {
            $side = strtolower((string)($ln->side ?? 'earning'));
            $amt  = (float) ($ln->amount ?? 0);
            if ($side === 'deduction') $ded += abs($amt);
            else $gross += max(0, $amt);
        }
        $this->gross = $gross;
        $this->deductions = $ded;
        $this->net = $gross - $ded;
    }

    // Jika mau incremental:
    // private function accumulate(object $line): void
    // {
    //     $side = strtolower((string)($line->side ?? 'earning'));
    //     $amt  = (float) ($line->amount ?? 0);
    //     if ($side === 'deduction') $this->deductions += abs($amt);
    //     else $this->gross += max(0, $amt);
    //     $this->net = $this->gross - $this->deductions;
    // }
     /**
     * Ambil total amount dari komponen tertentu.
     * Jika komponen muncul beberapa kali, jumlahkan semua.
     */
    public function getComponentTotal(string $code): float
    {
        $sum = 0.0;
        foreach ($this->lines as $ln) {
            $c = $ln->componentCode ?? $ln->component_code ?? null;
            if (strtoupper((string)$c) === strtoupper($code)) {
                $sum += (float)($ln->amount ?? 0);
            }
        }
        return $sum;
    }

    public function warn(string $msg): void
    {
        $this->diagnostics[] = $msg;
    }
}
