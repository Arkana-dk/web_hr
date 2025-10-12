<?php

namespace App\Domain\Payroll\Rules;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;

/**
 * OvertimeRule
 *
 * - Menghasilkan 1 baris komponen lembur untuk tiap karyawan (jika jam lembur > 0).
 * - Sumber jam lembur: $ctx['overtime_hours'][$employee->id] (float, jam).
 * - Tarif lembur:
 *    1) Jika ada rate "OVERTIME" di $ctx['rates'] (mis. unit = 'IDR/hour'), pakai itu.
 *    2) Jika tidak ada, fallback ke skema Indonesia umum: BASIC / 173, dengan multiplier 1.5x (jam 1) + 2.0x (jam berikutnya).
 * - Tidak menulis DB; hanya mengembalikan line object agar kompatibel dengan kalkulator existing.
 */
class OvertimeRule
{
    /**
     * Bangun baris (line) lembur. Aman untuk dipanggil berulang.
     *
     * @param array    $ctx          Context kalkulasi (period, rates, overtime_hours, dll).
     * @param Employee $employee     Model karyawan.
     * @param array    $currentLines Deretan line yang sudah dihitung rule lain (untuk membaca BASIC).
     *
     * @return array<\stdClass>      Array berisi 0 atau 1 line lembur.
     */
    public function buildLines(array $ctx, Employee $employee, array $currentLines = []): array
    {
        $empId = (int) ($employee->id ?? 0);

        // 1) Ambil total jam lembur karyawan dari context (sudah diakumulasi sebelumnya).
        $hours = (float) ($ctx['overtime_hours'][$empId] ?? 0.0);
        if ($hours <= 0) {
            return []; // Tidak ada lembur dalam periode.
        }

        // 2) Cari komponen OVERTIME (untuk metadata & side).
        //    Kalau tidak ada, semua field akan diisi default yang aman.
        $comp = DB::table('pay_components')->where('code', 'OVERTIME')->first();

        // 3) Coba cari rate OVERTIME dari $ctx['rates'], dengan prioritas:
        //    - Pay group spesifik
        //    - Default ('default')
        //    Saat ini kita mengharapkan unit 'IDR/hour' atau serupa (mengandung '/hour').
        [$hourly, $basisMeta] = $this->resolveHourlyRateFromRates($ctx, $comp);

        // 4) Jika tidak dapat rate per jam dari konfigurasi, fallback ke BASIC/173.
        //    BASIC diambil dari currentLines (hasil ProrataBasicRule) atau gaji pokok employee.
        if ($hourly <= 0) {
            [$hourly, $basisMeta] = $this->resolveHourlyByBasic173($employee, $currentLines);
        }

        // Tidak punya rate sama sekali → tidak bisa hitung lembur.
        if ($hourly <= 0) {
            return [];
        }

        // 5) Hitung amount lembur.
        //    Jika sumber rate dari konfigurasi (IDR/hour): total = jam * rate (tanpa multiplier khusus).
        //    Jika sumber dari BASIC/173: pakai multiplier 1.5x utk 1 jam pertama + 2.0x sisanya.
        $useBasic173 = ($basisMeta['source'] ?? '') === 'BASIC/173';

        if ($useBasic173) {
            $first  = min(1.0, $hours);
            $rest   = max(0.0, $hours - $first);
            $amount = ($first * 1.5 + $rest * 2.0) * $hourly;
            $basisMeta['schema'] = '1.5x (first 1h) + 2.0x (next)';
        } else {
            // Rate per jam absolut dari konfigurasi → tidak pakai multiplier default.
            $amount = $hours * $hourly;
            $basisMeta['schema'] = 'flat x hours';
        }

        if ($amount <= 0) {
            return [];
        }

        // 6) Tentukan side (earning/deduction) secara aman.
        $side = $this->resolvePostingSide($comp);

        // 7) Bangun 1 line lembur.
        $line = (object) [
            // Kode & tipe komponen (snake dan camel untuk kompatibilitas)
            'component_code' => 'OVERTIME',
            'componentCode'  => 'OVERTIME',
            'component_type' => 'overtime',
            'componentType'  => 'overtime',

            // Referensi komponen (opsional)
            'component_id'   => $comp->id   ?? null,
            'name'           => $comp->name ?? 'Overtime',

            // Nilai perhitungan
            'quantity'       => round($hours, 2),     // total jam
            'rate'           => round($hourly, 6),    // rate per jam
            'amount'         => round($amount, 2),    // total rupiah lembur

            // Metadata tambahan untuk audit/trace
            'meta'           => $basisMeta,

            // Penanda sisi (earning/deduction). Default earning jika tidak jelas.
            'side'           => $side ?: 'earning',
        ];

        return [$line];
    }

    /**
     * Kompat lama: langsung merge ke $result->lines.
     * Disarankan pakai buildLines() untuk gaya pipeline baru.
     */
    public function apply($ctx, Employee $employee, $result): void
    {
        $current = (isset($result->lines) && is_array($result->lines)) ? $result->lines : [];
        $lines   = $this->buildLines($ctx, $employee, $current);

        if (!empty($lines)) {
            $result->lines = array_merge($current, $lines);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper Methods
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Ambil rate per jam dari $ctx['rates'] untuk komponen OVERTIME jika ada.
     * Format $ctx['rates'][component_id][pay_group_id|default] = array of objects (rate rows).
     * Mengembalikan [hourly, meta] → hourly=0 artinya tidak ketemu/invalid.
     */
    private function resolveHourlyRateFromRates(array $ctx, $overtimeComponent): array
    {
        $meta = ['source' => 'RATES'];
        $hourly = 0.0;

        $componentId = (int) ($overtimeComponent->id ?? 0);
        if ($componentId <= 0) {
            return [0.0, $meta]; // komponen tidak ada di DB
        }

        // Ambil bucket rates untuk component id ini
        $buckets = $ctx['rates'][$componentId] ?? null;
        if (!$buckets) {
            return [0.0, $meta];
        }

        // Pilih daftar kandidat: pay group → default
        $groupId = $ctx['pay_group']->id ?? null;
        $rows    = null;

        if ($groupId !== null && isset($buckets[$groupId])) {
            $rows = $buckets[$groupId];
        } elseif (isset($buckets['default'])) {
            $rows = $buckets['default'];
        }
        if (!$rows) {
            return [0.0, $meta];
        }

        // Pilih rate yang overlap periode dan paling baru (effective_start paling baru).
        $period = $ctx['period'] ?? ['start' => '1000-01-01', 'end' => '9999-12-31'];
        $picked = null;

        foreach ($rows as $r) {
            $rStart  = $r->effective_start ?? '1000-01-01';
            $rEnd    = $r->effective_end   ?? '9999-12-31';
            $overlap = ($rStart <= $period['end']) && ($rEnd >= $period['start']);
            if (!$overlap) continue;

            if ($picked === null || ($rStart > $picked->effective_start)) {
                $picked = $r;
            }
        }

        if (!$picked) {
            return [0.0, $meta];
        }

        // Normalisasi unit & rate
        $unitRaw = strtolower(trim((string) ($picked->unit ?? '')));
        $rateVal = (float) ($picked->rate ?? 0);

        // Jika unit menunjukkan rate per jam (mis. 'idr/hour', 'idr / hour', dsb.)
        if ($rateVal > 0 && strpos($unitRaw, '/hour') !== false) {
            $hourly = $rateVal;
            $meta['unit']  = $picked->unit;
            $meta['rate']  = $rateVal;
            $meta['note']  = 'use configured IDR/hour';
            return [$hourly, $meta];
        }

        // Tidak cocok → biarkan 0 agar jatuh ke BASIC/173
        return [0.0, $meta];
    }

    /**
     * Fallback: hitung rate per jam dari BASIC/173.
     * BASIC diambil dari line 'BASIC' yang sudah ada (mis. ProrataBasicRule) atau dari salary karyawan.
     */
    private function resolveHourlyByBasic173(Employee $employee, array $currentLines): array
    {
        $basic = 0.0;

        // Cek BASIC dari hasil rule sebelumnya (kompat nama snake/camel).
        foreach ($currentLines as $ln) {
            $code = $ln->component_code ?? $ln->componentCode ?? null;
            if (strtoupper((string) $code) === 'BASIC') {
                $amt = $ln->amount ?? null;
                if (is_numeric($amt)) {
                    $basic = (float) $amt;
                }
                break;
            }
        }

        // Fallback: gaji pokok di employee (jika ada).
        if ($basic <= 0 && isset($employee->salary) && is_numeric($employee->salary)) {
            $basic = (float) $employee->salary;
        }

        $hourly = $basic > 0 ? ($basic / 173.0) : 0.0;

        return [
            $hourly,
            [
                'source' => 'BASIC/173',
                'basic'  => round($basic, 2),
                'note'   => 'standard hourly = BASIC / 173',
            ],
        ];
    }

    /**
     * Tentukan side (earning/deduction) dari metadata komponen.
     * Prioritas: posting_side → kind → default 'earning'.
     */
    private function resolvePostingSide($component): string
    {
        $postingSide = strtolower((string) ($component->posting_side ?? ''));
        if ($postingSide === 'earning' || $postingSide === 'deduction') {
            return $postingSide;
        }

        $kind = strtolower((string) ($component->kind ?? ''));

        // Set jenis-jenis kind yang jelas deduction
        $deductionKinds = [
            'deduction', 'tax', 'pph21', 'pph21_employee',
            'bpjs_ee', 'jht_ee', 'jp_ee', 'jks_ee', 'jpk_ee',
        ];

        return in_array($kind, $deductionKinds, true) ? 'deduction' : 'earning';
    }
}
