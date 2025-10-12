<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Attendance;
use App\Models\Notification;
use App\Models\AttendanceLocationSetting;
use App\Models\WorkSchedule;
use Carbon\Carbon;


class AttendanceController extends Controller
{
    use AuthorizesRequests;
    // ...
    public function create()
    {
        $employee = Auth::user()->employee;
        $today = now()->toDateString();

        $attendance = $employee->attendances()->where('date', $today)->first();
        $lateReasonRequired = $attendance && $attendance->status === 'late' && !$attendance->notes;

        // Ambil shift dari work schedule hari ini
        $workSchedule = \App\Models\WorkSchedule::where('employee_id', $employee->id)
            ->whereDate('work_date', $today)
            ->with('shift') // penting agar bisa ambil shift->end_time
            ->first();

        $shiftEndTime = optional($workSchedule?->shift)->end_time ?? '00:00:00';

        return view('employee.pages.attendance.create', compact('attendance', 'lateReasonRequired', 'shiftEndTime'));

        }   


        public function index()
    {
       
    }


    public function show(Attendance $attendance)
    {
        $this->authorize('view', $attendance);

        $shift = optional($attendance->employee->scheduleFor($attendance->date))->shift;

        return view('employee.pages.attendance.show', compact('attendance', 'shift'));
    }

   public function store(Request $request)
{
    $employee = Auth::user()->employee;
    $today = Carbon::today('Asia/Jakarta');
    $now = Carbon::now('Asia/Jakarta');

    // Ambil atau buat data presensi hari ini
    $attendance = Attendance::firstOrNew([
        'employee_id' => $employee->id,
        'date' => $today->toDateString(),
    ]);

    // Tambahan log: deteksi apakah record baru
    Log::info('Attendance object created', [
        'is_new_record' => !$attendance->exists,
        'employee_id'   => $employee->id,
        'date'          => $today->toDateString(),
        'current_data'  => $attendance->toArray(),
    ]);


    $workSchedule = WorkSchedule::where('employee_id', $employee->id)
        ->whereDate('work_date', $today)
        ->with('shift')
        ->first();

    if (!$workSchedule || !$workSchedule->shift) {
        return back()->with('error', 'Anda tidak memiliki jadwal kerja hari ini.');
    }

    // Validasi lokasi
    $insideRadius = false;
    $nearestLocation = null;
    foreach (AttendanceLocationSetting::all() as $location) {
        $distance = $this->calculateDistance(
            $request->latitude, $request->longitude,
            $location->latitude, $location->longitude
        );

        if ($distance <= $location->radius) {
            $insideRadius = true;
            $nearestLocation = $location->location_name;
            break;
        }
    }

    if (!$insideRadius) {
        Log::warning('Presensi ditolak karena di luar radius.', [
            'employee_id' => $employee->id,
            'lat' => $request->latitude,
            'lng' => $request->longitude,
            'timestamp' => now(),
        ]);
        return back()->with('error', 'Anda di luar radius lokasi.');
    }

    $folder = 'employee/attendance_photos/' . $today->format('Y-m-d');

    // === PRESENSI MASUK ===
    if (!$attendance->check_in_time) {
        $request->validate([
            'photo'     => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $path = $request->file('photo')->store($folder, 'public');

        $attendance->check_in_time = $now;
        $attendance->check_in_latitude = $request->latitude;
        $attendance->check_in_longitude = $request->longitude;
        $attendance->check_in_location = $nearestLocation;
        $attendance->check_in_photo_path = $path;

        $shiftStart = Carbon::parse($workSchedule->shift->start_time)->setDateFrom($now);
        if ($now->gt($shiftStart)) {
            $attendance->status = 'late';
            $attendance->notes = 'Terlambat masuk kerja';
                // ✅ Kirim notifikasi "terlambat"
            Notification::create([
                'employee_id' => $employee->id,
                'title'       => 'Terlambat Masuk',
                'message'     => $employee->name . ' terlambat check-in pada ' . now()->format('H:i d-m-Y'),
                'type'        => 'late',
            ]);
            Log::info('Notifikasi berhasil dibuat untuk keterlambatan.', [
                'employee_id' => $employee->id,
                'timestamp' => now()->toDateTimeString(),
            ]);


        } else {
            $attendance->status = 'present';
        }
        Log::info('Data Presensi Pulang', [
            'employee_id' => $employee->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'checkout_reason' => $request->checkout_reason,
            'submitted_at' => now()->toDateTimeString(),
        ]);

        Log::info('About to save Attendance', [
    'data' => $attendance->getDirty(), // field yang berubah (yang akan di-insert/update)
]);
        $attendance->save();

        return back()->with([
            'success' => 'Presensi masuk berhasil disimpan. Anda terlambat masuk.',
            'show_late_reason_modal' => true,
            'attendance_id' => $attendance->id,
        ]);
    }

    // === PRESENSI PULANG ===
    // === PRESENSI PULANG ===
if (!$attendance->check_out_time) {
    $request->validate([
        'photo'     => 'required|image|mimes:jpg,jpeg,png|max:2048',
        'latitude'  => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
    ]);

    $now = Carbon::now('Asia/Jakarta');
    $shiftEnd = Carbon::parse($workSchedule->shift->end_time)->setDateFrom($now)->timezone('Asia/Jakarta');

    if ($now->lt($shiftEnd) && !$request->filled('checkout_reason')) {
        return back()->with('error', 'Belum waktunya pulang. Harap isi alasan pulang cepat.');
    }

    $path = $request->file('photo')->store($folder, 'public');

    Log::info('Presensi Pulang', [
        'employee_id' => $employee->id,
        'check_in' => $attendance->check_in_time,
        'check_out' => $now->toDateTimeString(),
        'shift_end' => $shiftEnd->toDateTimeString(),
        'alasan' => $request->checkout_reason,
    ]);

    $attendance->check_out_time = $now;
    $attendance->check_out_latitude = $request->latitude;
    $attendance->check_out_longitude = $request->longitude;
    $attendance->check_out_location = $nearestLocation;
    $attendance->check_out_photo_path = $path;

    if ($request->filled('checkout_reason')) {
        $attendance->checkout_reason = $request->checkout_reason;
    }
        if ($now->lt($shiftEnd)) {
        Notification::create([
            'employee_id' => $employee->id,
            'title'       => 'Pulang Sebelum Waktu',
            'message'     => $employee->name . ' melakukan check-out lebih awal pada ' . now()->format('H:i d-m-Y') . ' dengan alasan: ' . $request->checkout_reason,
            'type'        => 'early_leave',
        ]);
    }


    $attendance->save();

    return back()->with('success', 'Presensi pulang berhasil disimpan.' .
        ($now->lt($shiftEnd) ? ' Anda pulang sebelum jam kerja selesai. Alasan: ' . $request->checkout_reason : '')
    );
}


}

// Controller Tambahan
public function updateLateReason(Request $request)
{
    $request->validate([
        'late_reason' => 'required|string|max:255',
        'attendance_id' => 'required|exists:attendances,id'
    ]);

    $attendance = Attendance::findOrFail($request->attendance_id);
   
    $this->authorize('update', $attendance);

    $attendance->notes = $request->late_reason;
    $attendance->save();

    return back()->with('success', 'Alasan keterlambatan berhasil disimpan.');
}



    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
