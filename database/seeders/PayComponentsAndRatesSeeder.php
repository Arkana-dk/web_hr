<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PayComponentsAndRatesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = now();

            // --- Ambil daftar kolom yang tersedia supaya aman di berbagai skema ---
            $pcCols  = Schema::getColumnListing('pay_components');
            $pcrCols = Schema::getColumnListing('pay_component_rates');

            // Helper untuk hanya menyimpan key yang ada di tabel
            $onlyCols = function (array $row, array $cols) {
                return collect($row)->filter(fn($v, $k) => in_array($k, $cols, true))->all();
            };

            // =========================
            // 1) PAY COMPONENTS MASTER
            // =========================
            // direction: 'earning'|'deduction'|'statutory'|'overtime'
            // category  : bebas (allowance, bonus, bpjs, etc) jika kolom ada
            $components = [
                // === CORE ===
                ['code'=>'BASIC',          'name'=>'Gaji Pokok',                  'direction'=>'earning',  'category'=>'core',     'is_taxable'=>true,  'is_recurring'=>true,  'description'=>'Gaji pokok per bulan'],
                ['code'=>'ALLOW_FIX',      'name'=>'Tunjangan Tetap',             'direction'=>'earning',  'category'=>'allowance','is_taxable'=>true,  'is_recurring'=>true],
                ['code'=>'ALLOW_VAR',      'name'=>'Tunjangan Variabel',          'direction'=>'earning',  'category'=>'allowance','is_taxable'=>true,  'is_recurring'=>true],
                ['code'=>'OVERTIME',       'name'=>'Lembur',                      'direction'=>'overtime', 'category'=>'overtime', 'is_taxable'=>true,  'is_recurring'=>false],

                // === ALLOWANCE UMUM (banyak) ===
                ['code'=>'MEAL',           'name'=>'Uang Makan (Harian)',         'direction'=>'earning',  'category'=>'meal',     'is_taxable'=>false, 'is_recurring'=>true],
                ['code'=>'TRANSPORT',      'name'=>'Uang Transport (Harian)',     'direction'=>'earning',  'category'=>'transport','is_taxable'=>false, 'is_recurring'=>true],
                ['code'=>'ATTEND_BONUS',   'name'=>'Bonus Kehadiran',             'direction'=>'earning',  'category'=>'bonus',    'is_taxable'=>true,  'is_recurring'=>true],
                ['code'=>'POSITION',       'name'=>'Tunjangan Jabatan',           'direction'=>'earning',  'category'=>'allowance','is_taxable'=>true,  'is_recurring'=>true],
                ['code'=>'SKILL',          'name'=>'Tunjangan Keahlian',          'direction'=>'earning',  'category'=>'allowance','is_taxable'=>true,  'is_recurring'=>true],
                ['code'=>'PHONE',          'name'=>'Tunjangan Komunikasi',        'direction'=>'earning',  'category'=>'allowance','is_taxable'=>true,  'is_recurring'=>true],
                ['code'=>'HOUSING',        'name'=>'Tunjangan Housing',           'direction'=>'earning',  'category'=>'allowance','is_taxable'=>true,  'is_recurring'=>true],
                ['code'=>'SHIFT',          'name'=>'Tunjangan Shift (Harian)',    'direction'=>'earning',  'category'=>'shift',    'is_taxable'=>true,  'is_recurring'=>true],
                ['code'=>'SHIFT_NIGHT',    'name'=>'Tunjangan Shift Malam',       'direction'=>'earning',  'category'=>'shift',    'is_taxable'=>true,  'is_recurring'=>true],
                ['code'=>'HAZARD',         'name'=>'Tunjangan Bahaya',            'direction'=>'earning',  'category'=>'allowance','is_taxable'=>true,  'is_recurring'=>true],
                ['code'=>'FAMILY_SPOUSE',  'name'=>'Tunjangan Keluarga (Suami/Istri)', 'direction'=>'earning','category'=>'allowance','is_taxable'=>true,'is_recurring'=>true],
                ['code'=>'FAMILY_CHILD',   'name'=>'Tunjangan Anak',              'direction'=>'earning',  'category'=>'allowance','is_taxable'=>true,  'is_recurring'=>true],
                ['code'=>'MEDICAL',        'name'=>'Tunjangan Kesehatan',         'direction'=>'earning',  'category'=>'allowance','is_taxable'=>true,  'is_recurring'=>true],
                ['code'=>'INTERNET',       'name'=>'Tunjangan Internet',          'direction'=>'earning',  'category'=>'allowance','is_taxable'=>true,  'is_recurring'=>true],
                ['code'=>'FUEL',           'name'=>'Tunjangan BBM',               'direction'=>'earning',  'category'=>'allowance','is_taxable'=>true,  'is_recurring'=>true],
                ['code'=>'PARKING',        'name'=>'Tunjangan Parkir',            'direction'=>'earning',  'category'=>'allowance','is_taxable'=>false, 'is_recurring'=>true],
                ['code'=>'REMOTE',         'name'=>'Tunjangan Dinas Luar Kota',   'direction'=>'earning',  'category'=>'allowance','is_taxable'=>true,  'is_recurring'=>false],
                ['code'=>'AREA',           'name'=>'Tunjangan Penempatan Khusus', 'direction'=>'earning',  'category'=>'allowance','is_taxable'=>true,  'is_recurring'=>true],
                ['code'=>'ONCALL',         'name'=>'On-Call/Standby',             'direction'=>'earning',  'category'=>'allowance','is_taxable'=>true,  'is_recurring'=>false],
                ['code'=>'NIGHT_ALLOW',    'name'=>'Tunjangan Malam (flat)',      'direction'=>'earning',  'category'=>'allowance','is_taxable'=>true,  'is_recurring'=>true],
                ['code'=>'PROJECT',        'name'=>'Tunjangan Proyek',            'direction'=>'earning',  'category'=>'allowance','is_taxable'=>true,  'is_recurring'=>false],
                ['code'=>'LAUNDRY',        'name'=>'Tunjangan Laundry',           'direction'=>'earning',  'category'=>'allowance','is_taxable'=>false, 'is_recurring'=>true],
                ['code'=>'GRADE',          'name'=>'Tunjangan Grade',             'direction'=>'earning',  'category'=>'allowance','is_taxable'=>true,  'is_recurring'=>true],
                ['code'=>'RETENTION',      'name'=>'Retention Bonus',             'direction'=>'earning',  'category'=>'bonus',    'is_taxable'=>true,  'is_recurring'=>false],
                ['code'=>'KPI_BONUS',      'name'=>'Bonus Kinerja (KPI)',         'direction'=>'earning',  'category'=>'bonus',    'is_taxable'=>true,  'is_recurring'=>false],
                ['code'=>'YEAR_END_BONUS', 'name'=>'Bonus Akhir Tahun',           'direction'=>'earning',  'category'=>'bonus',    'is_taxable'=>true,  'is_recurring'=>false],
                ['code'=>'SALES_COMM',     'name'=>'Komisi Penjualan',            'direction'=>'earning',  'category'=>'commission','is_taxable'=>true, 'is_recurring'=>false],

                // === THR (statutory/bonus tahunan) ===
                ['code'=>'THR',            'name'=>'Tunjangan Hari Raya (THR)',   'direction'=>'earning',  'category'=>'thr',      'is_taxable'=>true,  'is_recurring'=>false],

                // === DEDUCTIONS UMUM ===
                ['code'=>'COOP',           'name'=>'Potongan Koperasi',           'direction'=>'deduction','category'=>'deduction','is_taxable'=>false, 'is_recurring'=>true],
                ['code'=>'LOAN',           'name'=>'Potongan Kasbon/Pinjaman',    'direction'=>'deduction','category'=>'deduction','is_taxable'=>false, 'is_recurring'=>true],
                ['code'=>'UNION',          'name'=>'Iuran Serikat',               'direction'=>'deduction','category'=>'deduction','is_taxable'=>false, 'is_recurring'=>true],
                ['code'=>'UNPAID_LEAVE',   'name'=>'Potongan Cuti Tidak Dibayar', 'direction'=>'deduction','category'=>'deduction','is_taxable'=>false, 'is_recurring'=>true],
                ['code'=>'LATE_PENALTY',   'name'=>'Potongan Keterlambatan',      'direction'=>'deduction','category'=>'deduction','is_taxable'=>false, 'is_recurring'=>true],
                ['code'=>'ABSENCE_PEN',    'name'=>'Potongan Alpa',               'direction'=>'deduction','category'=>'deduction','is_taxable'=>false, 'is_recurring'=>true],
                ['code'=>'MEAL_DED',       'name'=>'Pemotongan Konsumsi',         'direction'=>'deduction','category'=>'deduction','is_taxable'=>false, 'is_recurring'=>true],
                ['code'=>'PPH21_EE',       'name'=>'PPh21 Karyawan',              'direction'=>'deduction','category'=>'tax',      'is_taxable'=>false, 'is_recurring'=>true],

                // === STATUTORY (BPJS/JKM/JP) ===
                ['code'=>'BPJSKES_EE',     'name'=>'BPJS Kesehatan (Karyawan)',   'direction'=>'statutory','category'=>'bpjs',     'is_taxable'=>false, 'is_recurring'=>true],
                ['code'=>'JHT_EE',         'name'=>'BPJS TK - JHT (Karyawan)',    'direction'=>'statutory','category'=>'bpjs',     'is_taxable'=>false, 'is_recurring'=>true],
                ['code'=>'JP_EE',          'name'=>'BPJS TK - JP (Karyawan)',     'direction'=>'statutory','category'=>'bpjs',     'is_taxable'=>false, 'is_recurring'=>true],
                ['code'=>'BPJSKES_ER',     'name'=>'BPJS Kesehatan (Perusahaan)', 'direction'=>'statutory','category'=>'bpjs',     'is_taxable'=>false, 'is_recurring'=>true],
                ['code'=>'JHT_ER',         'name'=>'BPJS TK - JHT (Perusahaan)',  'direction'=>'statutory','category'=>'bpjs',     'is_taxable'=>false, 'is_recurring'=>true],
                ['code'=>'JP_ER',          'name'=>'BPJS TK - JP (Perusahaan)',   'direction'=>'statutory','category'=>'bpjs',     'is_taxable'=>false, 'is_recurring'=>true],
                ['code'=>'JKK_ER',         'name'=>'BPJS TK - JKK (Perusahaan)',  'direction'=>'statutory','category'=>'bpjs',     'is_taxable'=>false, 'is_recurring'=>true],
                ['code'=>'JKM_ER',         'name'=>'BPJS TK - JKM (Perusahaan)',  'direction'=>'statutory','category'=>'bpjs',     'is_taxable'=>false, 'is_recurring'=>true],
            ];

            // Upsert komponen berdasarkan code
            foreach ($components as $c) {
                $base = [
                    'code'        => $c['code'],
                    'name'        => $c['name'],
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
                // kolom opsional
                $opt  = [
                    'direction'    => $c['direction'] ?? null,
                    'category'     => $c['category']  ?? null,
                    'is_taxable'   => $c['is_taxable'] ?? null,
                    'is_recurring' => $c['is_recurring'] ?? null,
                    'description'  => $c['description'] ?? null,
                ];
                DB::table('pay_components')->updateOrInsert(
                    ['code' => $c['code']],
                    $onlyCols(array_merge($base, $opt), $pcCols)
                );
            }

            // Ambil mapping code->id
            $pcMap = DB::table('pay_components')->pluck('id', 'code')->all();

            // =========================
            // 2) DEFAULT RATES (banyak)
            // =========================
            // type: fixed | daily | percent | formula
            // unit: month|day|shift|hour (opsional, cuma diisi kalau kolom ada)
            // meta: json (opsional) — untuk cap, base_component, dsb.
            $rates = [
                // --- Allowance umum (rata-rata) ---
                ['code'=>'ALLOW_FIX',    'type'=>'fixed',   'value'=>500000, 'unit'=>'month', 'note'=>'Tunjangan tetap'],
                ['code'=>'ALLOW_VAR',    'type'=>'fixed',   'value'=>300000, 'unit'=>'month'],
                ['code'=>'MEAL',         'type'=>'daily',   'value'=>25000,  'unit'=>'day',   'note'=>'Uang makan harian'],
                ['code'=>'TRANSPORT',    'type'=>'daily',   'value'=>15000,  'unit'=>'day'],
                ['code'=>'ATTEND_BONUS', 'type'=>'fixed',   'value'=>200000, 'unit'=>'month', 'note'=>'Bonus hadir full'],
                ['code'=>'POSITION',     'type'=>'percent', 'value'=>10,     'unit'=>'month', 'meta'=>['base'=>'BASIC']],
                ['code'=>'SKILL',        'type'=>'fixed',   'value'=>200000, 'unit'=>'month'],
                ['code'=>'PHONE',        'type'=>'fixed',   'value'=>150000, 'unit'=>'month'],
                ['code'=>'HOUSING',      'type'=>'fixed',   'value'=>500000, 'unit'=>'month'],
                ['code'=>'SHIFT',        'type'=>'daily',   'value'=>20000,  'unit'=>'day'],
                ['code'=>'SHIFT_NIGHT',  'type'=>'daily',   'value'=>30000,  'unit'=>'day'],
                ['code'=>'HAZARD',       'type'=>'daily',   'value'=>50000,  'unit'=>'day'],
                ['code'=>'FAMILY_SPOUSE','type'=>'percent', 'value'=>10,     'unit'=>'month', 'meta'=>['base'=>'BASIC']],
                // Anak: 2% per anak maks 2 anak → formula
                ['code'=>'FAMILY_CHILD', 'type'=>'formula', 'formula'=>'min(children,2) * 0.02 * BASIC', 'unit'=>'month'],
                ['code'=>'MEDICAL',      'type'=>'fixed',   'value'=>200000, 'unit'=>'month'],
                ['code'=>'INTERNET',     'type'=>'fixed',   'value'=>100000, 'unit'=>'month'],
                ['code'=>'FUEL',         'type'=>'fixed',   'value'=>300000, 'unit'=>'month'],
                ['code'=>'PARKING',      'type'=>'fixed',   'value'=>50000,  'unit'=>'month', 'note'=>'Non-taxable cap sesuai aturan'],
                ['code'=>'REMOTE',       'type'=>'fixed',   'value'=>350000, 'unit'=>'month'],
                ['code'=>'AREA',         'type'=>'fixed',   'value'=>250000, 'unit'=>'month'],
                ['code'=>'ONCALL',       'type'=>'fixed',   'value'=>75000,  'unit'=>'month'],
                ['code'=>'NIGHT_ALLOW',  'type'=>'fixed',   'value'=>100000, 'unit'=>'month'],
                ['code'=>'PROJECT',      'type'=>'fixed',   'value'=>500000, 'unit'=>'month'],
                ['code'=>'LAUNDRY',      'type'=>'fixed',   'value'=>50000,  'unit'=>'month'],
                ['code'=>'GRADE',        'type'=>'fixed',   'value'=>300000, 'unit'=>'month'],
                ['code'=>'RETENTION',    'type'=>'fixed',   'value'=>0,      'unit'=>'month'], // isi saat diperlukan
                ['code'=>'KPI_BONUS',    'type'=>'fixed',   'value'=>0,      'unit'=>'month'], // by performance
                ['code'=>'YEAR_END_BONUS','type'=>'fixed',  'value'=>0,      'unit'=>'month'],
                ['code'=>'SALES_COMM',   'type'=>'fixed',   'value'=>0,      'unit'=>'month'], // bisa diubah percent dari sales

                // --- Lembur (tarif acuan regulasi 1/173) ---
                // Nilai dihitung oleh OvertimeRule; seed meta untuk referensi
                ['code'=>'OVERTIME',     'type'=>'formula', 'formula'=>'OT_RATE(1/173 * BASIC)', 'unit'=>'hour', 'meta'=>[
                    'first_hour_multiplier'=>1.5, 'next_hours_multiplier'=>2.0, 'rest_day_first2h'=>2.0, 'rest_day_next'=>3.0
                ]],

                // --- THR (umum: proporsional masa kerja) ---
                ['code'=>'THR',          'type'=>'formula', 'formula'=>'min(months_of_service/12,1) * BASIC', 'unit'=>'event'],

                // --- Deductions umum ---
                ['code'=>'COOP',         'type'=>'fixed',   'value'=>100000, 'unit'=>'month'],
                ['code'=>'LOAN',         'type'=>'fixed',   'value'=>0,      'unit'=>'month'],
                ['code'=>'UNION',        'type'=>'fixed',   'value'=>0,      'unit'=>'month'],
                ['code'=>'UNPAID_LEAVE', 'type'=>'formula', 'formula'=>'unpaid_days * (BASIC/ (work_days_in_month))', 'unit'=>'day'],
                ['code'=>'LATE_PENALTY', 'type'=>'formula', 'formula'=>'late_minutes * 1000', 'unit'=>'minute'],
                ['code'=>'ABSENCE_PEN',  'type'=>'fixed',   'value'=>0,      'unit'=>'month'],
                ['code'=>'MEAL_DED',     'type'=>'fixed',   'value'=>0,      'unit'=>'month'],
                ['code'=>'PPH21_EE',     'type'=>'formula', 'formula'=>'pph21()', 'unit'=>'month'],

                // --- BPJS/JKM/JP (default wajar; sesuaikan cap regulasi) ---
                // Karyawan
                ['code'=>'BPJSKES_EE', 'type'=>'percent', 'value'=>1.0, 'unit'=>'month', 'meta'=>['base'=>'BASIC+ALLOW_FIX', 'cap'=>12000000]],
                ['code'=>'JHT_EE',     'type'=>'percent', 'value'=>2.0, 'unit'=>'month', 'meta'=>['base'=>'BASIC+ALLOW_FIX']],
                ['code'=>'JP_EE',      'type'=>'percent', 'value'=>1.0, 'unit'=>'month', 'meta'=>['base'=>'BASIC+ALLOW_FIX', 'cap'=>10000000]],

                // Perusahaan (biaya pemberi kerja)
                ['code'=>'BPJSKES_ER', 'type'=>'percent', 'value'=>4.0,   'unit'=>'month', 'meta'=>['base'=>'BASIC+ALLOW_FIX', 'cap'=>12000000]],
                ['code'=>'JHT_ER',     'type'=>'percent', 'value'=>3.7,   'unit'=>'month', 'meta'=>['base'=>'BASIC+ALLOW_FIX']],
                ['code'=>'JP_ER',      'type'=>'percent', 'value'=>2.0,   'unit'=>'month', 'meta'=>['base'=>'BASIC+ALLOW_FIX', 'cap'=>10000000]],
                ['code'=>'JKK_ER',     'type'=>'percent', 'value'=>1.27,  'unit'=>'month', 'meta'=>['base'=>'BASIC+ALLOW_FIX']], // kelas risiko umum
                ['code'=>'JKM_ER',     'type'=>'percent', 'value'=>0.30,  'unit'=>'month', 'meta'=>['base'=>'BASIC+ALLOW_FIX']],
            ];

            // Bersihkan rate lama agar tidak dobel? (opsional)
            // DB::table('pay_component_rates')->truncate();

           foreach ($rates as $r) {
                if (!isset($pcMap[$r['code']])) {
                    continue;
                }

                $row = [
                    'pay_component_id' => $pcMap[$r['code']],
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];

                // --- Effective date fields (penting untuk skema kamu) ---
                if (in_array('effective_date', $pcrCols, true)) {
                    $row['effective_date'] = \Carbon\Carbon::today();
                }
                if (in_array('effective_start', $pcrCols, true)) {
                    $row['effective_start'] = \Carbon\Carbon::today();   // WAJIB diisi
                }
                if (in_array('effective_end', $pcrCols, true)) {
                    $row['effective_end'] = null;                        // boleh null
                }

                // --- Status aktif & unit/currency jika ada kolomnya ---
                if (in_array('is_active', $pcrCols, true))  $row['is_active'] = true;
                if (in_array('unit', $pcrCols, true))       $row['unit'] = $r['unit'] ?? null;
                if (in_array('currency', $pcrCols, true))   $row['currency'] = 'IDR';
                if (in_array('meta', $pcrCols, true) && isset($r['meta'])) {
                    $row['meta'] = json_encode($r['meta']);
                }

                // --- Representasi nilai, menyesuaikan skema kolom ---
                // Prefer type/value jika tabel mendukung
                if (in_array('type', $pcrCols, true)) {
                    $row['type'] = $r['type'] ?? null;
                    if (isset($r['value']) && in_array('value', $pcrCols, true)) {
                        $row['value'] = $r['type'] === 'percent' ? (float)$r['value'] : (float)$r['value'];
                    }
                    if (isset($r['formula']) && in_array('formula', $pcrCols, true)) {
                        $row['formula'] = $r['formula'];
                    }
                } else {
                    // Skema alternatif: amount/percentage/formula
                    if (isset($r['formula']) && in_array('formula', $pcrCols, true)) {
                        $row['formula'] = $r['formula'];
                    }
                    if (isset($r['value'])) {
                        // fixed/daily → amount, percent → percentage, fallback → value
                        if (in_array('amount', $pcrCols, true) && (($r['type'] ?? null) === 'fixed' || ($r['type'] ?? null) === 'daily')) {
                            $row['amount'] = (float)$r['value'];
                        } elseif (in_array('percentage', $pcrCols, true) && ($r['type'] ?? null) === 'percent') {
                            $row['percentage'] = (float)$r['value'];
                        } elseif (in_array('value', $pcrCols, true)) {
                            $row['value'] = (float)$r['value'];
                        }
                    }
                }

                DB::table('pay_component_rates')->insert($onlyCols($row, $pcrCols));
            }


            // =========================
            // 3) (Optional) Attach ke Pay Group
            // =========================
            if (Schema::hasTable('pay_groups') && Schema::hasTable('pay_group_components')) {
                $pgs = DB::table('pay_groups')->pluck('id')->all();
                $pgcCols = Schema::getColumnListing('pay_group_components');

                foreach ($pgs as $pgId) {
                    foreach ($pcMap as $code => $pcId) {
                        // contoh: attach semua komponen ke semua pay group (silakan sesuaikan)
                        $pgc = [
                            'pay_group_id'     => $pgId,
                            'pay_component_id' => $pcId,
                            'is_active'        => true,
                            'created_at'       => $now,
                            'updated_at'       => $now,
                        ];
                        // upsert sederhana berdasar unique (pg, component)
                        $exists = DB::table('pay_group_components')
                            ->where('pay_group_id', $pgId)
                            ->where('pay_component_id', $pcId)
                            ->exists();

                        if (!$exists) {
                            DB::table('pay_group_components')->insert($onlyCols($pgc, $pgcCols));
                        }
                    }
                }
            }
        });
    }
}
