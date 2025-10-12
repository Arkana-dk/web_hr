<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $employee = optional(Auth::user())->employee;
        abort_if(!$employee, 403, 'Employee profile tidak ditemukan.');

        $query = Notification::where('employee_id', $employee->id);

        // === Filters ===
        if ($request->filled('only')) {
            if ($request->only === 'unread')   $query->where('is_read', false);
            elseif ($request->only === 'read') $query->where('is_read', true);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('message', 'like', "%{$q}%");
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        $notifications = $query->latest()->paginate(10)->withQueryString();

        return view('employee.pages.notifications.index', compact('notifications'));
    }

    public function markAsRead(Notification $notification)
    {
        $employee = optional(Auth::user())->employee;
        abort_if(!$employee, 403);
        abort_unless($notification->employee_id === $employee->id, 403);

        if (! $notification->is_read) {
            $notification->forceFill(['is_read' => true])->save();
        }

        return back()->with('success', 'Notifikasi ditandai sebagai sudah dibaca.');
    }

    public function markAllAsRead()
    {
        $employee = optional(Auth::user())->employee;
        abort_if(!$employee, 403);

        Notification::where('employee_id', $employee->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', 'Semua notifikasi ditandai sebagai sudah dibaca.');
    }
}
