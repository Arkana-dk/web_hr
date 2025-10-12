<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{PayComponent, PayComponentRate, PayGroup, PayGroupComponent};

class PayComponentsStarterSeeder extends Seeder
{
    public function run(): void
    {
        $group = PayGroup::first(); // pakai PG-DEFAULT
        if (!$group) return;

        // Master komponen (pastikan tabel pay_components sudah ada)
        $defs = [
            ['code'=>'BASIC','name'=>'Gaji Pokok','type'=>'earning','category'=>'basic','attributes'=>json_encode(['calc_type'=>'fixed'])],
            ['code'=>'MEAL_DAILY','name'=>'Uang Makan (Harian)','type'=>'earning','category'=>'allowance','attributes'=>json_encode(['calc_type'=>'daily'])],
            ['code'=>'TRANS_DAILY','name'=>'Transport (Harian)','type'=>'earning','category'=>'allowance','attributes'=>json_encode(['calc_type'=>'daily'])],
            ['code'=>'OT_WD','name'=>'Lembur Hari Kerja','type'=>'earning','category'=>'overtime','attributes'=>json_encode(['calc_type'=>'hourly'])],
            ['code'=>'OT_WE','name'=>'Lembur Akhir Pekan','type'=>'earning','category'=>'overtime','attributes'=>json_encode(['calc_type'=>'hourly'])],
            ['code'=>'OT_PH','name'=>'Lembur Tgl Merah','type'=>'earning','category'=>'overtime','attributes'=>json_encode(['calc_type'=>'hourly'])],
            ['code'=>'BPJS_JHT_EE','name'=>'JHT (Karyawan)','type'=>'deduction','category'=>'bpjs','attributes'=>json_encode(['calc_type'=>'percent_of_basic','percent'=>2])],
            ['code'=>'BPJS_JP_EE','name'=>'JP (Karyawan)','type'=>'deduction','category'=>'bpjs','attributes'=>json_encode(['calc_type'=>'percent_of_basic','percent'=>1])],
        ];

        foreach ($defs as $d) {
            $comp = PayComponent::firstOrCreate(
                ['code'=>$d['code']],
                [
                    'name'=>$d['name'],
                    'type'=>$d['type'],
                    'category'=>$d['category'],
                    'effective_start'=>now()->toDateString(),
                    'attributes'=>$d['attributes'],
                ]
            );
            PayGroupComponent::firstOrCreate(
                ['pay_group_id'=>$group->id, 'pay_component_id'=>$comp->id],
                ['is_active'=>true]
            );
        }

        // Rates contoh (meal/transport per-day; OT koefisien pakai rule, jadi rate per_hour dihitung dari salary/173)
        $meal = PayComponent::where('code','MEAL_DAILY')->first();
        $trans= PayComponent::where('code','TRANS_DAILY')->first();
        if ($meal) PayComponentRate::firstOrCreate(
            ['pay_component_id'=>$meal->id,'pay_group_id'=>$group->id,'unit'=>'per_day','effective_start'=>now()->toDateString()],
            ['rate'=>15000]
        );
        if ($trans) PayComponentRate::firstOrCreate(
            ['pay_component_id'=>$trans->id,'pay_group_id'=>$group->id,'unit'=>'per_day','effective_start'=>now()->toDateString()],
            ['rate'=>10000]
        );
    }
}
