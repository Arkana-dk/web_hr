<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * GET /admin/notifications
     * Filters: type (string), read ("0"|"1"|""), date (Y-m-d)
     */
public function index(Request $request)
{
    $q = Notification::with('employee')->latest();

    // Filter jenis notifikasi
    if ($request->filled('type')) {
        $q->where('type', $request->input('type'));
    }

    // Filter status baca ('0' atau '1') — jangan pakai filled() karena '0' dianggap kosong
    if ($request->has('read') && $request->input('read') !== '') {
        $q->where('is_read', $request->input('read') === '1');
    }

    // Filter tanggal — normalize ke Y-m-d lalu whereDate()
    if ($request->filled('date')) {
        $raw = trim($request->input('date'));
        $normalized = null;

        // coba format umum dulu
        foreach (['Y-m-d','d-m-Y','d/m/Y','m/d/Y','d-m-y','d/m/y','m/d/y','Y/m/d'] as $fmt) {
            try {
                $normalized = \Carbon\Carbon::createFromFormat($fmt, $raw)->format('Y-m-d');
                break;
            } catch (\Throwable $e) {}
        }
        // fallback parse bebas
        if (!$normalized) {
            try { $normalized = \Carbon\Carbon::parse($raw)->format('Y-m-d'); } catch (\Throwable $e) {}
        }

        if ($normalized) {
            $q->whereDate('created_at', $normalized);
        }
    }

    $notifications = $q->paginate(10)->appends($request->query());

    return view('admin.pages.notifications.index', compact('notifications'));
}



    /** PATCH /admin/notifications/{notification}/read */
    public function markAsRead(Notification $notification)
    {
        if (! $notification->is_read) {
            $notification->forceFill(['is_read' => true])->save();
        }

        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    /** PATCH /admin/notifications/mark-all-as-read */
    public function markAllAsRead(Request $request)
    {
        Notification::where('is_read', false)->update(['is_read' => true]);

        return back()->with('success', 'Semua notifikasi telah ditandai sudah dibaca.');
    }
}