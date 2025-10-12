<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Attendance;

class AttendancePolicy
{
    /**
     * Lihat presensi (employee hanya milik sendiri)
     */
    public function view(User $user, Attendance $attendance): bool
    {
        return optional($user->employee)->id === $attendance->employee_id;
    }

    /**
     * Ubah presensi (misal untuk edit alasan telat)
     */
   public function update(User $user, Attendance $attendance): bool
    {
        return optional($user->employee)->id === $attendance->employee_id;
    }


    /**
     * Hapus presensi — hanya admin boleh (jika dibutuhkan)
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->hasRole('admin');
    }

    protected $policies = [
    \App\Models\Attendance::class => \App\Policies\AttendancePolicy::class,
];

}
