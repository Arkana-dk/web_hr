{{-- resources/views/admin/pages/calendar.blade.php --}}
@extends('layouts.master')

@section('title', 'Calendar')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 text-gray-800">Calendar</h1>
    <a href="{{ route('admin.calendars.settings') }}" class="btn btn-sm btn-primary">
      <i class="fas fa-cog"></i> Settings
    </a>
  </div>
  <div class="card"><div class="card-body">
    <div id="calendar" style="max-width:900px; margin:0 auto;"></div>
  </div></div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet"/>
<style>
  /* Minggu (Sunday) date number in red, larger */
  .fc-daygrid-day.fc-day-sun .fc-daygrid-day-number {
    color: #e74a3b !important;
    font-size: 1.25rem;
    font-weight: bold;
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  new FullCalendar.Calendar(document.getElementById('calendar'), {
    locale: 'id',
    initialView: 'dayGridMonth',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek'
    },
    events: {
      url: '{{ route("admin.calendars.events") }}',
      failure() { alert('Gagal memuat data libur.'); }
    },
    selectable: false,
    eventDidMount(arg) {
      // set each event’s background & border from your controller’s color props:
      arg.el.style.backgroundColor = arg.event.backgroundColor;
      arg.el.style.borderColor     = arg.event.borderColor;
      arg.el.style.color           = arg.event.textColor;
    },
    eventMouseEnter(arg) {
      // optional tooltip on hover
      const tooltip = document.createElement('div');
      tooltip.className = 'fc-tooltip';
      tooltip.innerText = arg.event.extendedProps.description;
      document.body.appendChild(tooltip);
      arg.el._tooltip = tooltip;
    },
    eventMouseMove(arg, ev) {
      const tooltip = arg.el._tooltip;
      if (tooltip) {
        tooltip.style.top  = ev.pageY + 10 + 'px';
        tooltip.style.left = ev.pageX + 10 + 'px';
      }
    },
    eventMouseLeave(arg) {
      arg.el._tooltip?.remove();
      arg.el._tooltip = null;
    }
  }).render();
});
</script>
@endpush
