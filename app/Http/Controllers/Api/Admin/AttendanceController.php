<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Calendar;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $q = Attendance::with('employee')
            ->when($request->filled('date'),   fn($q) => $q->whereDate('date', $request->date))
            ->when($request->filled('status'), fn($q) => $q->where('status',    $request->status))
            ->latest('date');

        $perPage = $request->input('per_page', 15);
        $data    = $q->paginate($perPage);

        return response()->json($data);
    }

    public function show(Attendance $attendance)
    {
        $attendance->load('employee');
        return response()->json($attendance);
    }

    public function update(Request $request, Attendance $attendance)
    {
        // blokir hari libur
        if (Calendar::where('date', $attendance->date)->where('type','Libur')->exists()) {
            return response()->json([
                'message' => 'Tidak bisa mengubah: hari libur.'
            ], 422);
        }

        $payload = $request->validate([
            'check_in_time'     => 'nullable|date_format:H:i',
            'check_out_time'    => 'nullable|date_format:H:i|after:check_in_time',
            'status'            => 'required|in:present,late,absent,excused',
            'notes'             => 'nullable|string',
            'check_in_location' => 'nullable|string|max:255',
        ]);

        $attendance->update($payload);

        return response()->json($attendance);
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();
        return response()->json(['message'=>'Deleted']);
    }
}
