<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\LeaveType;
use App\Models\LeavePolicy;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Seed jenis cuti dulu
        $types = [
            ['code'=>'AL','name'=>'Cuti Tahunan','is_paid'=>true,  'requires_attachment'=>false],
            ['code'=>'SL','name'=>'Cuti Sakit',   'is_paid'=>true,  'requires_attachment'=>true],
            ['code'=>'UL','name'=>'Unpaid Leave', 'is_paid'=>false, 'requires_attachment'=>false],
        ];

        foreach ($types as $t) {
            LeaveType::updateOrCreate(['code' => $t['code']], $t);
        }

        // 2) Baru ambil AL
        $al = LeaveType::where('code', 'AL')->first();

        // 3) (Opsional) Seed policy default kalau tabelnya ada
        if ($al && Schema::hasTable('leave_policies')) {
            LeavePolicy::updateOrCreate(
                [
                    'pay_group_id'    => null,                // global (semua pay group)
                    'leave_type_id'   => $al->id,
                    'effective_start' => now()->toDateString()
                ],
                [
                    'effective_end' => null,
                    'rules' => [
                        'min_notice_days'      => 3,
                        'max_lead_days'        => 90,
                        'max_days_per_request' => 5,
                        'allow_negative_balance'=> false,
                        'exclude_weekends'     => true,
                        'approval_flow'        => ['manager','hr'],
                    ],
                ]
            );
        }
    }
}
