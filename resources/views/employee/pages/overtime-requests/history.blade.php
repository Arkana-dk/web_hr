@extends('layouts.master')

@section('title', 'Riwayat Pengajuan Lembur')

@section('content')
<div class="container py-4">
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="card border-0 shadow rounded-4">
    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
      <h4 class="mb-0 text-primary"><i class="fas fa-clock me-2"></i>Riwayat Pengajuan Lembur</h4>
      <a href="{{ route('employee.overtime.requests.create') }}" class="btn btn-success rounded-pill shadow-sm">
        <i class="fas fa-plus me-1"></i> Ajukan Lembur
      </a>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle text-center">
          <thead class="table-light">
            <tr>
              <th>No</th>
              <th>Tanggal</th>
              <th>Jam Mulai</th>
              <th>Jam Selesai</th>
              <th>Alasan</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse($requests as $req)
              <tr>
                <td>{{ $requests->firstItem() + $loop->index }}</td>
                <td>{{ \Carbon\Carbon::parse($req->date)->format('d M Y') }}</td>
                <td>{{ $req->start_time }}</td>
                <td>{{ $req->end_time }}</td>
                <td>{{ $req->reason ?? '—' }}</td>
                <td>
                  @if($req->status === 'approved')
                    <span class="badge bg-success-soft text-success">Disetujui</span>
                  @elseif($req->status === 'rejected')
                    <span class="badge bg-danger-soft text-danger">Ditolak</span>
                  @else
                    <span class="badge bg-warning-soft text-warning">Menunggu</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted">Belum ada pengajuan lembur.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-center mt-4">
        {{ $requests->links('pagination::bootstrap-4') }}
      </div>
    </div>
  </div>
</div>
@endsection
