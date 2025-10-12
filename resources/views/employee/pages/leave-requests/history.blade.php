@extends('layouts.master')
@section('title','Riwayat Cuti')
@include('components.leave.styles-soft')

@section('content')
<div class="container-fluid">
  {{-- HERO --}}
  <div class="card hero-card mb-3">
    <div class="hero-body d-flex justify-content-between align-items-center">
      <div>
        <div class="h3 fw-bold mb-0">Riwayat Cuti Saya</div>
        <div class="opacity-75">Daftar pengajuan cuti</div>
      </div>
      <a href="{{ route('employee.leave.request') }}" class="btn btn-light btn-pill btn-elev">
        <i class="fas fa-plus"></i> Ajukan Cuti
      </a>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card soft">
    <div class="table-responsive">
      <table class="table table-hover align-middle table-soft mb-0">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Jenis</th>
            <th>Rentang</th>
            <th>Hari</th>
            <th>Status</th>
            <th>Alasan</th>
            <th>Lampiran</th>
          </tr>
        </thead>
        <tbody>
          @forelse($leaveRequests as $req)
            @php
              $s = $req->start_date?->format('d M Y');
              $e = $req->end_date?->format('d M Y');
              $d = $req->days ?? ($req->start_date && $req->end_date ? $req->start_date->diffInDays($req->end_date)+1 : null);
            @endphp
            <tr>
              <td>{{ $req->created_at?->format('d M Y H:i') ?? '—' }}</td>
              <td>{{ $req->type?->name ?? '—' }}</td>
              <td>{{ $s }}{{ $e && $e !== $s ? ' – '.$e : '' }}</td>
              <td>{{ $d ?? '—' }}</td>
              <td>
                @switch($req->status)
                  @case('approved') <span class="badge bg-success-subtle text-success border">Approved</span> @break
                  @case('rejected') <span class="badge bg-danger-subtle text-danger border">Rejected</span> @break
                  @case('cancelled') <span class="badge bg-secondary-subtle text-secondary border">Cancelled</span> @break
                  @default <span class="badge bg-warning-subtle text-warning border">Pending</span>
                @endswitch
              </td>
              <td>{{ $req->reason ?? '—' }}</td>
              <td>
                @if(!empty($req->attachment_path))
                  <a href="{{ asset('storage/'.$req->attachment_path) }}" target="_blank">Lihat</a>
                @else —
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada pengajuan</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-body">
      {{ $leaveRequests->links() }}
    </div>
  </div>
</div>
@endsection
