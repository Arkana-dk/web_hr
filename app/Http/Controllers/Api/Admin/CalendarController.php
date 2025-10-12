<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Calendar;
use Illuminate\Support\Str;

class CalendarController extends Controller
{
    public function index()
    {
        $items = Calendar::orderByDesc('date')->get();
        return response()->json($items);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date'        => 'required|date|unique:calendars,date',
            'description' => 'required|string|max:255',
            'type'        => 'required|in:Masuk,Warning,Libur',
            'color'       => 'required|string|size:7',
        ]);

        $item = Calendar::create($data);
        return response()->json($item, 201);
    }

    public function show(Calendar $calendar)
    {
        return response()->json($calendar);
    }

    public function update(Request $request, Calendar $calendar)
    {
        $data = $request->validate([
            'date'        => "required|date|unique:calendars,date,{$calendar->id}",
            'description' => 'required|string|max:255',
            'type'        => 'required|in:Masuk,Warning,Libur',
            'color'       => 'required|string|size:7',
        ]);

        $calendar->update($data);
        return response()->json($calendar);
    }

    public function destroy(Calendar $calendar)
    {
        $calendar->delete();
        return response()->json(['message'=>'Deleted']);
    }

    /**
     * For fullcalendar event feed:
     */
    public function events(Request $request)
    {
        $from = $request->query('start');
        $to   = $request->query('end');

        $data = Calendar::whereBetween('date', [$from,$to])->get()->map(fn($e)=>[
            'id'            => $e->id,
            'title'         => Str::limit($e->description,20,'…'),
            'start'         => $e->date->toDateString(),
            'allDay'        => true,
            'extendedProps'=> [
                'description'=> $e->description,
                'type'       => $e->type,
            ],
            'backgroundColor'=> $e->color,
            'borderColor'    => $e->color,
            'textColor'      => '#fff',
        ]);

        return response()->json($data);
    }
}
