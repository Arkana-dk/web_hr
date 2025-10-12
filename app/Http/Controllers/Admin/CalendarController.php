<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Calendar;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CalendarController extends Controller
{
    /**
     * Show the calendar view.
     */
    public function index()
    {
        return view('admin.pages.calendar');
    }

    /**
     * Show settings page for managing calendar entries.
     */
    public function settings()
    {
        $items = Calendar::orderByDesc('date')->get();
        return view('admin.pages.calendar-settings', compact('items'));
    }

    /**
     * Fetch events for FullCalendar.
     */
    public function events(Request $request)
    {
        $from = $request->start;
        $to   = $request->end;

        $data = Calendar::whereBetween('date', [$from, $to])
            ->get()
            ->map(fn($e) => [
                'id'              => $e->id,
                'title'           => Str::limit($e->description, 20, '…'),
                'start'           => $e->date->toDateString(),
                'allDay'          => true,
                'extendedProps'   => [
                    'description' => $e->description,
                    'type'        => $e->type,
                ],
                'backgroundColor' => $e->color,
                'borderColor'     => $e->color,
                'textColor'       => '#ffffff',
            ]);

        return response()->json($data);
    }

    /**
     * Store a new calendar entry.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date'        => 'required|date|unique:calendars,date',
            'description' => 'required|string|max:255',
            'type'        => 'required|in:Masuk,Warning,Libur',
            'color'       => 'required|string|size:7',  // expect "#rrggbb"
        ]);

        Calendar::create($data);

        return redirect()
            ->route('admin.calendars.settings')
            ->with('success','Jadwal berhasil disimpan.');
    }

    /**
     * Show the form for editing a calendar entry.
     */
    public function edit(Calendar $calendar)
    {
        // reuse the same settings view but you could split out
        $items = Calendar::orderByDesc('date')->get();
        return view('admin.pages.calendar-settings', compact('items'))
               ->with('editing', $calendar);
    }

    /**
     * Update a calendar entry.
     */
    public function update(Request $request, Calendar $calendar)
    {
        $data = $request->validate([
            'date'        => "required|date|unique:calendars,date,{$calendar->id}",
            'description' => 'required|string|max:255',
            'type'        => 'required|in:Masuk,Warning,Libur',
            'color'       => 'required|string|size:7',
        ]);

        $calendar->update($data);

        return redirect()
            ->route('admin.calendars.settings')
            ->with('success','Jadwal berhasil diperbarui.');
    }

    /**
     * Remove a calendar entry.
     */
    public function destroy(Calendar $calendar)
    {
        $calendar->delete();

        return redirect()
            ->route('admin.calendars.settings')
            ->with('success','Jadwal berhasil dihapus.');
    }
}
