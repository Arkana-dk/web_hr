@extends('layouts.master')

@section('title', 'Riwayat Pengajuan Pindah Shift')

@section('content')
<div class="container py-4">
  <div class="card border-0 shadow rounded-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <h4 class="mb-0 text-primary"><i class="fas fa-random me-2"></i>Riwayat Pengajuan Pindah Shift</h4>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle text-center mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Tanggal</th>
              <th>Shift Asal</th>
              <th>Shift Tujuan</th>
              <th>Status</th>
              <th>Alasan</th>
            </tr>
          </thead>
          <tbody>
            @forelse($requests as $req)
              <tr>
                <td>{{ $requests->firstItem() + $loop->index }}</td>
                <td>{{ \Carbon\Carbon::parse($req->date)->format('d M Y') }}</td>
                <td>
                  {{ $req->fromShift->name ?? '-' }}
                  <div class="small text-muted">{{ $req->fromShift->start_time }} - {{ $req->fromShift->end_time }}</div>
                </td>
                <td>
                  {{ $req->toShift->name ?? '-' }}
                  <div class="small text-muted">{{ $req->toShift->start_time }} - {{ $req->toShift->end_time }}</div>
                </td>
                <td>
                  @if($req->status === 'approved')
                    <span class="badge bg-success-soft text-success">Disetujui</span>
                  @elseif($req->status === 'rejected')
                    <span class="badge bg-danger-soft text-danger">Ditolak</span>
                  @else
                    <span class="badge bg-warning-soft text-warning">Menunggu</span>
                  @endif
                </td>
                <td>{{ $req->reason ?? '—' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-muted text-center py-3">Belum ada pengajuan.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-center mt-3">
        {{ $requests->links('pagination::bootstrap-4') }}
      </div>
    </div>
  </div>
</div>
@endsection
