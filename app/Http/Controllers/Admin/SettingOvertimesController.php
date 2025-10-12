<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SettingOvertime;
use Illuminate\Http\Request;

class SettingOvertimesController extends Controller
{
    // Form edit
    public function edit()
    {
        // Ambil record pertama, jika belum ada maka buat dengan nilai default
        $setting = SettingOvertime::first();
        if (! $setting) {
            $setting = SettingOvertime::create([
                'rate_per_hour'  => 0,          // default rate lembur
                'paid_in_month'  => 'current',  // default enum sesuai migrasi
            ]);
        }

        return view('admin.pages.setting-overtime-create', compact('setting'));
    }

    // Update rate
    public function update(Request $request)
    {
        $request->validate([
            'rate_per_hour' => 'required|numeric|min:0',
        ]);

        $setting = SettingOvertime::first();
        if (! $setting) {
            // Jika entah kenapa belum ada, kita buat baru
            $setting = SettingOvertime::create([
                'rate_per_hour' => $request->rate_per_hour,
                'paid_in_month' => 'current',
            ]);
        } else {
            $setting->rate_per_hour = $request->rate_per_hour;
            $setting->save();
        }

        return redirect()
            ->route('admin.setting_overtime.edit')
            ->with('success', 'Rate lembur berhasil diperbarui.');
    }
}
