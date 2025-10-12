<?php

/**
 * File: database/seeders/PayrollSeeder.php (safe & robust)
 * - Hanya mengisi kolom yang benar-benar ada di DB.
 * - Menjamin semua PayComponent yang dipetakan ke PayGroup terbuat terlebih dahulu.
 * - Formula & rate disesuaikan koridor regulasi Kemnaker/BPJS.
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;
use Carbon\Carbon;

use App\Models\PayGroup;
use App\Models\PayComponent;
use App\Models\PayGroupComponent;
use App\Models\PayComponentRate;

class PayrollSeeder extends Seeder
{
    private function onlyColumns(string $table, array $data): array
    {
        $cols = collect(Schema::getColumnListing($table));
        return collect($data)->filter(fn ($v, $k) => $cols->contains($k))->all();
    }

    private function ensureComponent(array $data): PayComponent
    {
        $key = Arr::only($data, ['code']);
        /** @var PayComponent $comp */
        $comp = PayComponent::withTrashed()->firstOrNew($key);
        $payload = $this->onlyColumns('pay_components', [
            'name'   => $data['name'] ?? $data['code'],
            'kind'   => $data['kind'] ?? null,
            'active' => $data['active'] ?? true,
            'notes'  => $data['notes'] ?? null,
            'attributes' => $data['attributes'] ?? null,
        ]);
        $comp->fill($payload);
        if (method_exists($comp, 'restore')) { $comp->restore(); }
        $comp->save();
        return $comp;
    }

    public function run(): void
    {
        DB::transaction(function () {
            $effective = Carbon::create(2025, 1, 1);

            // 1) Pay Groups
            $groups = [
                ['code' => 'REG',    'name' => 'Karyawan Tetap (5 hari kerja)', 'active' => true],
                ['code' => 'SHIFT',  'name' => 'Karyawan Shift',               'active' => true],
                ['code' => 'PKWT',   'name' => 'Karyawan Kontrak (PKWT)',     'active' => true],
            ];

            $groupMap = [];
            foreach ($groups as $g) {
                $group = PayGroup::query()->firstOrCreate(
                    ['code' => $g['code']],
                    $this->onlyColumns('pay_groups', [
                        'name'   => $g['name'],
                        'active' => $g['active'] ?? true,
                        'notes'  => $g['notes'] ?? null,
                    ])
                );
                $groupMap[$g['code']] = $group->id;
            }

            // 2) Pay Components (tanpa field 'calculation')
            $components = [
                ['code' => 'BASIC',        'name' => 'Gaji Pokok',                   'kind' => 'earning',   'active' => true, 'notes' => 'Porsi upah pokok ≥ 75% dari (upah pokok + tunjangan tetap).'],
                ['code' => 'ALLOW_FIX',    'name' => 'Tunjangan Tetap',              'kind' => 'allowance', 'active' => true],
                ['code' => 'ALLOW_VAR',    'name' => 'Tunjangan Tidak Tetap',        'kind' => 'allowance', 'active' => true],
                ['code' => 'OVERTIME',     'name' => 'Upah Lembur',                  'kind' => 'earning',   'active' => true, 'notes' => 'Upah sejam = 1/173 × upah sebulan; 1.5x jam pertama & 2x jam berikutnya.'],
                ['code' => 'THR',          'name' => 'Tunjangan Hari Raya (THR)',    'kind' => 'earning',   'active' => true, 'notes' => '≥12 bln = 1 bulan upah; <12 bln pro-rata.'],
                ['code' => 'BPJSKES_EE',   'name' => 'BPJS Kesehatan (Pekerja 1%)',  'kind' => 'deduction', 'active' => true],
                ['code' => 'JHT_EE',       'name' => 'JHT (Pekerja 2%)',             'kind' => 'deduction', 'active' => true],
                ['code' => 'JP_EE',        'name' => 'JP (Pekerja 1%)',              'kind' => 'deduction', 'active' => true],
                ['code' => 'BPJSKES_ER',   'name' => 'BPJS Kesehatan (Pemberi Kerja 4%)', 'kind' => 'statutory', 'active' => true],
                ['code' => 'JHT_ER',       'name' => 'JHT (Pemberi Kerja 3.7%)',          'kind' => 'statutory', 'active' => true],
                ['code' => 'JP_ER',        'name' => 'JP (Pemberi Kerja 2%)',             'kind' => 'statutory', 'active' => true],
                ['code' => 'JKK_ER',       'name' => 'JKK (Pemberi Kerja)',               'kind' => 'statutory', 'active' => true],
                ['code' => 'JKM_ER',       'name' => 'JKM (Pemberi Kerja 0.3%)',          'kind' => 'statutory', 'active' => true],
            ];

            foreach ($components as $c) { $this->ensureComponent($c); }

            // Bangun ulang peta kode->id; jika ada yg hilang, buat minimal
            $codeList = array_column($components, 'code');
            $compMap = PayComponent::query()->whereIn('code', $codeList)->pluck('id', 'code')->all();
            foreach ($codeList as $code) {
                if (!isset($compMap[$code]) || empty($compMap[$code])) {
                    $fallback = $this->ensureComponent(['code' => $code, 'name' => $code, 'kind' => 'info']);
                    $compMap[$code] = $fallback->id;
                }
            }

            // 3) Mapping component per group
            $order = [
                'BASIC', 'ALLOW_FIX', 'ALLOW_VAR', 'OVERTIME', 'THR',
                'BPJSKES_EE', 'JHT_EE', 'JP_EE',
                'BPJSKES_ER', 'JHT_ER', 'JP_ER', 'JKK_ER', 'JKM_ER',
            ];

            foreach ($groupMap as $groupId) {
                $seq = 10;
                foreach ($order as $code) {
                    $pcId = $compMap[$code] ?? null;
                    if (!$pcId) {
                        if (property_exists($this, 'command') && $this->command) {
                            $this->command->warn("Lewati mapping '$code' karena ID komponen tidak ditemukan.");
                        }
                        continue;
                    }

                    $payload = $this->onlyColumns('pay_group_components', [
                        'sequence'  => $seq,
                        'mandatory' => in_array($code, ['BASIC','BPJSKES_EE','JHT_EE','BPJSKES_ER','JHT_ER','JKM_ER']),
                        'active'    => true,
                    ]);

                    PayGroupComponent::query()->firstOrCreate(
                        [
                            'pay_group_id'     => $groupId,
                            'pay_component_id' => $pcId,
                        ],
                        $payload
                    );
                    $seq += 10;
                }
            }

            // 4) Rates default (global)
            $rates = [
                ['comp' => 'BPJSKES_EE', 'unit' => 'percent', 'rate' => 1,    'formula' => 'min(base_upah, 12000000) * 0.01'],
                ['comp' => 'JHT_EE',     'unit' => 'percent', 'rate' => 2,    'formula' => 'base_upah * 0.02'],
                ['comp' => 'JP_EE',      'unit' => 'percent', 'rate' => 1,    'formula' => 'min(base_upah, jp_ceiling) * 0.01'],
                ['comp' => 'BPJSKES_ER', 'unit' => 'percent', 'rate' => 4,    'formula' => 'min(base_upah, 12000000) * 0.04'],
                ['comp' => 'JHT_ER',     'unit' => 'percent', 'rate' => 3.7,  'formula' => 'base_upah * 0.037'],
                ['comp' => 'JP_ER',      'unit' => 'percent', 'rate' => 2,    'formula' => 'min(base_upah, jp_ceiling) * 0.02'],
                ['comp' => 'JKK_ER',     'unit' => 'percent', 'rate' => 0.24, 'formula' => 'base_upah * (risk_rate/100)'],
                ['comp' => 'JKM_ER',     'unit' => 'percent', 'rate' => 0.3,  'formula' => 'base_upah * 0.003'],
                ['comp' => 'OVERTIME',   'unit' => 'hour',    'rate' => 1/173, 'formula' => '(base_upah/173) * (1.5*jam_ot_pertama + 2*jam_ot_berikutnya)'],
                ['comp' => 'THR',        'unit' => 'month',   'rate' => 1,     'formula' => 'masa_kerja>=12 ? base_upah : base_upah*(masa_kerja/12)'],
            ];

            foreach ($rates as $r) {
                $pcId = $compMap[$r['comp']] ?? null;
                if (!$pcId) { continue; }

                PayComponentRate::query()->firstOrCreate(
                    [
                        'pay_component_id' => $pcId,
                        'pay_group_id'     => null,
                        'unit'             => $r['unit'],
                        'effective_start'  => $effective,
                    ],
                    $this->onlyColumns('pay_component_rates', [
                        'rate'          => $r['rate'],
                        'formula'       => $r['formula'] ?? null,
                        'effective_end' => null,
                    ])
                );
            }
        });
    }
}
