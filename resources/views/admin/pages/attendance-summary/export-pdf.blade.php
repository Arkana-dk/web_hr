{{-- resources/views/admin/pages/attendance-summary/export-pdf.blade.php --}}
@php
  use Carbon\Carbon;
  $period = Carbon::create($year, $month, 1)->translatedFormat('F Y');
@endphp
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Ringkasan Presensi - {{ $period }}</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    h2 { margin: 0 0 10px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 6px; }
    th { background: #f3f3f3; text-align: left; }
  </style>
</head>
<body>
  <h2>Ringkasan Presensi – {{ $period }}</h2>
  <table>
    <thead>
      <tr>
        @foreach($headers as $h)
          <th>{{ $h }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $r)
        <tr>
          @foreach($r as $c)
            <td>{{ $c }}</td>
          @endforeach
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
