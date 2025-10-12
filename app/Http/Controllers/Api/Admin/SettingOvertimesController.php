<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SettingOvertime;
use Illuminate\Http\Request;

class SettingOvertimesController extends Controller
{
    /**
     * GET  /api/admin/setting-overtime
     */
    public function edit()
    {
        $setting = SettingOvertime::first() 
                 ?? SettingOvertime::create([
                        'rate_per_hour' => 0,
                        'paid_in_month' => 'current',
                    ]);

        return response()->json(['data' => $setting]);
    }

    /**
     * PUT  /api/admin/setting-overtime
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'rate_per_hour' => 'required|numeric|min:0',
            'paid_in_month' => 'nullable|in:current,next',
        ]);

        $setting = SettingOvertime::first() 
                 ?? new SettingOvertime();

        $setting->fill([
            'rate_per_hour' => $data['rate_per_hour'],
            'paid_in_month' => $data['paid_in_month'] ?? $setting->paid_in_month,
        ])->save();

        return response()->json([
            'message' => 'Overtime setting updated',
            'data'    => $setting
        ]);
    }
}
