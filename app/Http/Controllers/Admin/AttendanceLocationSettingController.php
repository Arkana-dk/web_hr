<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLocationSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class AttendanceLocationSettingController extends Controller
    {
        /**
         * Display a listing of the resource.
         */

     public function index()
    {
        $locations = AttendanceLocationSetting::all();
        return view('admin.pages.attendance-location-settings.index', compact('locations'));
    }

    public function create()
    {
        return view('admin.pages.attendance-location-settings.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'location_name' => 'required|string|max:255|unique:attendance_location_settings,location_name',
            'latitude' => [
                'required', 'numeric',
                Rule::unique('attendance_location_settings')->where(fn ($query) =>
                    $query->where('latitude', $request->latitude)
                        ->where('longitude', $request->longitude)
                ),
            ],
            'longitude' => 'required|numeric',
            'radius' => 'required|numeric|min:1',
        ]);

        AttendanceLocationSetting::create($request->only('location_name', 'latitude', 'longitude', 'radius'));

        return redirect()->route('admin.attendance-location-settings.index')
            ->with('success', 'Lokasi berhasil ditambahkan');
    }


    public function destroy($id)
    {
        $location = AttendanceLocationSetting::findOrFail($id);
        $location->delete();

        return redirect()->route('admin.attendance-location-settings.index')
            ->with('success', 'Lokasi berhasil dihapus.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
{
    $location = AttendanceLocationSetting::findOrFail($id);
    return view('admin.pages.attendance-location-settings.edit', compact('location'));
}

public function update(Request $request, $id)
{
    $location = AttendanceLocationSetting::findOrFail($id);

    $request->validate([
        'location_name' => [
            'required', 'string', 'max:255',
            Rule::unique('attendance_location_settings')->ignore($id)
        ],
        'latitude' => [
            'required', 'numeric',
            Rule::unique('attendance_location_settings')
                ->ignore($id)
                ->where(fn ($query) =>
                    $query->where('latitude', $request->latitude)
                          ->where('longitude', $request->longitude)
                ),
        ],
        'longitude' => 'required|numeric',
        'radius' => 'required|numeric|min:1',
    ]);

    $location->update($request->only('location_name', 'latitude', 'longitude', 'radius'));

    return redirect()->route('admin.attendance-location-settings.index')
        ->with('success', 'Lokasi berhasil diperbarui');
}


    /**
     * Update the specified resource in storage.
     */
    /**
     * Remove the specified resource from storage.
     */

}
