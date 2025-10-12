<?php

return [
    // Jam kerja default (dipakai kalau PayGroup->schedule tidak ada)
    'default_daily_hours' => 8,

    // Mapping status dari tabel attendances
    'present_statuses'  => ['present','late','hadir','work','wfo','onsite','wfh','remote'],
    'paid_statuses'     => [
        'present','late','hadir','work','wfo','onsite','wfh','remote',
        'leave','izin','cuti','sick','sakit','training','excused',
    ],

    // Status approval yang dianggap disetujui (attendance_requests/leave_requests/overtime_requests)
    'approved_statuses' => ['approved','acc','accepted','disetujui','approved_by_hr','approved_manager'],

    // Tipe request yang dibayar / tidak dibayar
    'paid_req_types'    => [
        'annual_leave','paid_leave','leave','izin','cuti','sick','sakit',
        'wfh','remote','training','marriage','maternity','paternity',
    ],
    'unpaid_req_types'  => ['unpaid_leave','alpha','absent'],

    // Lembur (tanpa setting_overtime)
    'ot_component_code' => 'OT',
    'ot_round_to'       => 0.5, // pembulatan jam (0.5 = setengah jam)
    'overtime_tiers'    => [
        'first_hour' => 1.5,
        'next_hours' => 2.0,
    ],
];
