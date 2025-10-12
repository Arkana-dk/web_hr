@extends('layouts.master')

@section('title', 'Detail Presensi - ' . $employee->name)

@section('content')
<div class="container-fluid">

  <div class="mb-3">
    <a href="{{ route('admin.attendance-summary.index', ['month'=>$month,'year'=>$year]) }}"
       class="btn btn-light btn-sm">
      <i class="fas fa-arrow-left"></i> Kembali
    </a>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <h4>{{ $employee->name }}</h4>
      <p class="text-muted mb-1">
        {{ $employee->position->name ?? '-' }} • {{ $employee->department->name ?? '-' }}
      </p>
      <div>
        Bulan: <strong>{{ \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}</strong>
      </div>
    </div>
  </div>

  {{-- Summary --}}
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card shadow-sm border-left-success">
        <div class="card-body">
          <i class="fas fa-check-circle text-success"></i> Hadir: {{ $summary['present'] }}
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-left-warning">
        <div class="card-body">
          <i class="fas fa-clock text-warning"></i> Terlambat: {{ $summary['late'] }}
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-left-danger">
        <div class="card-body">
          <i class="fas fa-times-circle text-danger"></i> Absen: {{ $summary['absent'] }}
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-left-info">
        <div class="card-body">
          <i class="fas fa-user-md text-info"></i> Izin/Cuti: {{ $summary['cuti'] }}
        </div>
      </div>
    </div>
  </div>

  {{-- Tabel detail presensi --}}
  <div class="card shadow-sm">
    <div class="card-body">
      <h5 class="mb-3">Detail Kehadiran</h5>
      <table class="table table-sm table-bordered">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Status</th>
            <th>Jam Masuk</th>
            <th>Jam Keluar</th>
            <th>Keterangan</th>
          </tr>
        </thead>
        <tbody>
          @forelse($attendances as $att)
            <tr>
              <td>{{ \Carbon\Carbon::parse($att->date)->translatedFormat('d M Y') }}</td>
              <td>
                @if($att->status == 'present')
                  <span class="badge badge-success">Hadir</span>
                @elseif($att->status == 'late')
                  <span class="badge badge-warning">Terlambat</span>
                @elseif($att->status == 'absent')
                  <span class="badge badge-danger">Absen</span>
                @else
                  <span class="badge badge-secondary">{{ ucfirst($att->status) }}</span>
                @endif
              </td>
              <td>{{ $att->check_in ? \Carbon\Carbon::parse($att->check_in)->format('H:i') : '-' }}</td>
              <td>{{ $att->check_out ? \Carbon\Carbon::parse($att->check_out)->format('H:i') : '-' }}</td>
              <td>{{ $att->note ?? '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-muted">Tidak ada data presensi.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
