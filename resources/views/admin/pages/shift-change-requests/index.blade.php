@extends('layouts.master')

@section('title','Persetujuan Pindah Shift')

@section('content')
<div class="container-fluid">

  {{-- Flash --}}
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  {{-- ================= HERO CARD ================= --}}
  <div class="card hero-card shadow-sm mb-4">
    <div class="hero-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
      <div>
        <h2 class="hero-title mb-1">Persetujuan Pindah Shift</h2>
        <div class="hero-meta">
          Total pengajuan: <strong>{{ $requests->total() }}</strong>
          <span class="d-none d-md-inline">•</span>
          <span class="d-block d-md-inline">Tampil: <strong>{{ $requests->count() }}</strong> (halaman ini)</span>
        </div>
      </div>
    </div>
  </div>

  {{-- ================= FILTER MINI HERO ================= --}}
  <form method="GET" action="{{ request()->url() }}" class="card filter-card mb-3">
    <div class="card-body">
      <div class="filter-grid pill align-items-end">
        <div>
          <label class="filter-label">Status</label>
          <select name="status" class="form-select">
            <option value="">Semua Status</option>
            <option value="pending"  {{ request('status')=='pending'  ? 'selected' : '' }}>Pending</option>
            <option value="approved" {{ request('status')=='approved' ? 'selected' : '' }}>Disetujui</option>
            <option value="rejected" {{ request('status')=='rejected' ? 'selected' : '' }}>Ditolak</option>
          </select>
        </div>

        <div>
          <label class="filter-label">Tanggal</label>
          <input type="date" name="date" class="form-control"
                 value="{{ request('date') }}">
        </div>

        <div class="ml-md-auto">
          <div class="toolbar">
            <button class="btn btn-primary btn-pill" type="submit">
              <i class="fas fa-filter mr-1"></i> Terapkan
            </button>
            <a href="{{ route('admin.shift-change.index') }}" class="btn btn-outline-secondary btn-pill">
              Reset
            </a>
          </div>
        </div>
      </div>
    </div>
  </form>

  {{-- ================= TABLE ================= --}}
  <div class="card shadow rounded-4">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered table-hover table-sticky align-middle w-100 table-nowrap mb-0">
          <thead class="thead-light">
            <tr>
              <th style="width:50px">No</th>
              <th>Nama</th>
              <th>Tanggal</th>
              <th>Shift Asal</th>
              <th>Shift Tujuan</th>
              <th>Alasan</th>
              <th>Status</th>
              <th style="width:150px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($requests as $req)
              @php
                $badge = match($req->status) {
                  'approved' => 'success',
                  'rejected' => 'danger',
                  'pending'  => 'warning',
                  default    => 'secondary',
                };
              @endphp
              <tr>
                <td>{{ $requests->firstItem() + $loop->index }}</td>
                <td class="fw-semibold">{{ $req->employee->name }}</td>
                <td>{{ \Carbon\Carbon::parse($req->date)->translatedFormat('d M Y') }}</td>
                <td>
                  {{ $req->fromShift->name }}<br>
                  <small class="text-muted">{{ $req->fromShift->start_time }}–{{ $req->fromShift->end_time }}</small>
                </td>
                <td>
                  {{ $req->toShift->name }}<br>
                  <small class="text-muted">{{ $req->toShift->start_time }}–{{ $req->toShift->end_time }}</small>
                </td>
                <td class="text-wrap text-start" style="max-width:200px;">
                  {{ $req->reason ?? '—' }}
                </td>
                <td>
                  <span class="badge bg-{{ $badge }}">{{ ucfirst($req->status) }}</span>
                </td>
                <td>
                  @if($req->status === 'pending')
                    <div class="d-flex flex-wrap justify-content-center" style="gap:.35rem">
                      <form action="{{ route('admin.shift-change.approve', $req->id) }}" method="POST" class="d-inline">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-success btn-sm btn-pill">
                          <i class="fas fa-check"></i> Setujui
                        </button>
                      </form>
                      <form action="{{ route('admin.shift-change.reject', $req->id) }}" method="POST" class="d-inline">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-danger btn-sm btn-pill">
                          <i class="fas fa-times"></i> Tolak
                        </button>
                      </form>
                    </div>
                  @else
                    <span class="text-muted small fst-italic">Selesai</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-4 text-muted">Belum ada pengajuan shift.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center">
      <div class="small text-muted">
        Menampilkan {{ $requests->firstItem() }}–{{ $requests->lastItem() }} dari {{ $requests->total() }} data
      </div>
      {{ $requests->withQueryString()->links('pagination::bootstrap-4') }}
    </div>
  </div>

</div>
@endsection

@push('styles')
<style>
  :root{
    --hero-grad: linear-gradient(135deg,#0d6efd,#6f42c1);
  }

  /* Hero */
  .hero-card{
    background: var(--hero-grad);
    color:#fff;
    border:0;
    border-radius:1.25rem;
    overflow:hidden;
  }
  .hero-body{ padding: 1.1rem 1.25rem; }
  .hero-title{ font-weight:700; }
  .hero-meta{ opacity:.95; }
  .btn-pill{ border-radius:999px!important; }

  /* Filter */
  .filter-card{
    border:0; border-radius:1rem;
    background:
      linear-gradient(#ffffff,#ffffff) padding-box,
      linear-gradient(135deg, rgba(13,110,253,.25), rgba(111,66,193,.25)) border-box;
    border:1px solid transparent;
    box-shadow:0 10px 28px -20px rgba(13,110,253,.5);
  }
  .filter-card .card-body{ padding:1rem 1.25rem; }
  .filter-label{ font-size:.8rem; color:#6c757d; margin-bottom:.25rem }
  .pill .form-control, .pill .form-select{
    border-radius:999px; height:44px; padding:0 .9rem;
  }
  .toolbar{ display:flex; gap:.5rem; }

  /* Table */
  .table-sticky thead th{ position:sticky; top:0; background:#fff; z-index:2; }
  .table-nowrap th, .table-nowrap td{ white-space:nowrap; }
  .table td, .table th{ vertical-align:middle; }

  .pagination .page-link{ padding:.25rem .5rem; font-size:.875rem; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    @if(session('success'))
      Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: '{{ session('success') }}',
        timer: 2500,
        showConfirmButton: false
      });
    @endif

    @if(session('error'))
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: '{{ session('error') }}'
      });
    @endif
  });
</script>
@endpush
