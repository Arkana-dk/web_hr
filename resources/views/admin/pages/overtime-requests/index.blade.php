{{-- resources/views/admin/pages/overtime/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Pengajuan Lembur')

@section('content')
<div class="container-fluid">

  {{-- ================= HERO CARD ================= --}} 
  <div class="card hero-card shadow-sm mb-4">
    <div class="hero-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
      <div>
        <h2 class="hero-title mb-1">Pengajuan Lembur</h2>
        <div class="hero-meta">
          Total: <strong>{{ $overtimeRequests->total() }}</strong>
          <span class="d-none d-md-inline">•</span>
          <span class="d-block d-md-inline">Tampil: <strong>{{ $overtimeRequests->count() }}</strong> (halaman ini)</span>
          @isset($pendingOvertimeRequests)
            <span class="d-none d-md-inline">•</span>
            <span class="d-block d-md-inline">
              Pending: <strong>{{ $pendingOvertimeRequests }}</strong>
            </span>
          @endisset
        </div>
      </div>

      {{-- Actions (pill buttons) --}}
      <div class="hero-actions mt-3 mt-md-0">
        <a href="{{ route('admin.overtime-requests.create') }}" class="btn btn-success btn-pill btn-elev mr-2">
          <i class="fas fa-plus mr-1"></i> Tambah Pengajuan
        </a>
        <button id="downloadExcelBtn" type="button" class="btn btn-outline-light btn-pill">
          <i class="fas fa-file-excel mr-1"></i> Download Excel
        </button>
      </div>
    </div>
  </div>

  {{-- ================= FILTER (mini-hero) ================= --}}
<form action="{{ route('admin.overtime-requests.index') }}" method="GET" class="card filter-card mb-4">
  <div class="card-body">
    <div class="filter-grid pill align-items-end">
      <div class="control">
        <label class="filter-label mb-1">Dari</label>
        <input type="date" name="from_date" id="from_date" class="form-control w-160"
               value="{{ request('from_date') }}">
      </div>

      <div class="control">
        <label class="filter-label mb-1">Sampai</label>
        <input type="date" name="to_date" id="to_date" class="form-control w-160"
               value="{{ request('to_date') }}">
      </div>

      <div class="control">
        <label class="filter-label mb-1">Status</label>
        <select name="status" id="status" class="form-select w-160">
          <option value="">— Semua —</option>
          <option value="pending"  @selected(request('status')==='pending')>Pending</option>
          <option value="approved" @selected(request('status')==='approved')>Approved</option>
          <option value="rejected" @selected(request('status')==='rejected')>Rejected</option>
        </select>
      </div>

      <div class="ms-md-auto">
        <div class="toolbar">
          <button type="submit" class="btn btn-primary btn-pill">
            <i class="fas fa-filter mr-1"></i> Terapkan
          </button>
          <a href="{{ route('admin.overtime-requests.index') }}" class="btn btn-outline-secondary btn-pill">
            Reset
          </a>
        </div>
      </div>
    </div>
  </div>
</form>


  {{-- ================= KPI Cards (Transportasi) ================= --}}
  @if(!empty($transportRouteStats) && count($transportRouteStats))
    <div class="row">
      @foreach($transportRouteStats as $stat)
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card card-kpi border-left-primary shadow h-100 py-2">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                    Transportasi: {{ $stat->transport_route }}
                  </div>
                  <div class="h4 mb-0 font-weight-bold text-gray-800">
                    {{ $stat->total }} Pegawai
                  </div>
                </div>
                <i class="fas fa-bus fa-2x text-gray-300"></i>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif

  {{-- ================= TABLE ================= --}}
  <div class="card shadow rounded-4">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered table-hover table-sticky align-middle w-100 table-nowrap mb-0">
          <thead class="thead-light">
            <tr>
              <th style="width:64px">#</th>
              <th>Nama Pegawai</th>
              <th>Tanggal</th>
              <th>Waktu</th>
              <th>Hari</th>
              <th>Transport</th>
              <th>Makan</th>
              <th>Alasan</th>
              <th>Status</th>
              <th>Approved By</th>
              <th style="width:120px">Aksi</th>

            </tr>
          </thead>
          <tbody>
  @forelse ($overtimeRequests as $index => $req)
    <tr>
      {{-- No --}}
      <td>{{ $overtimeRequests->firstItem() + $index }}</td>

      {{-- Pegawai --}}
      <td>{{ $req->employee->name ?? '—' }}</td>

      {{-- Tanggal --}}
      <td class="fw-semibold">{{ \Carbon\Carbon::parse($req->date)->format('d M Y') }}</td>

      {{-- Jam Lembur --}}
      <td>{{ $req->start_time }} - {{ $req->end_time }}</td>

      {{-- Jenis Hari --}}
      <td>{{ ucfirst($req->day_type) }}</td>

      {{-- Rute Transport --}}
      <td>{{ $req->transport_route }}</td>

      {{-- Opsi Makan --}}
      <td>{{ $req->meal_option }}</td>

      {{-- Alasan --}}
      <td>{{ \Illuminate\Support\Str::limit($req->reason, 30) }}</td>

      {{-- Status --}}
      <td>
        @php
          $badgeColor = [
            'pending'  => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
          ][$req->status] ?? 'secondary';
        @endphp
        <span class="badge bg-{{ $badgeColor }}">
          {{ ucfirst($req->status) }}
        </span>
      </td>

      {{-- Disetujui Oleh --}}
      <td>
        @if(in_array($req->status, ['approved','rejected']))
          {{ $req->approver->name ?? '—' }}
        @else
          <span class="text-muted">—</span>
        @endif
      </td>

      {{-- Aksi --}}
      <td class="text-center">
        @if ($req->status === 'pending')
          {{-- Approve --}}
          <form action="{{ route('admin.overtime-requests.approve', $req->id) }}"
                method="POST" class="d-inline js-approve-form">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-sm btn-success btn-pill" title="Setujui">
              <i class="fas fa-check"></i>
            </button>
          </form>

          {{-- Reject --}}
          <form action="{{ route('admin.overtime-requests.reject', $req->id) }}"
                method="POST" class="d-inline js-reject-form">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-sm btn-danger btn-pill" title="Tolak">
              <i class="fas fa-times"></i>
            </button>
          </form>
        @else
          <span class="text-muted">—</span>
        @endif
      </td>
    </tr>
  @empty
    <tr>
      <td colspan="11" class="text-center py-4 text-muted">
        Belum ada pengajuan lembur.
      </td>
    </tr>
  @endforelse
</tbody>

        </table>
      </div>
    </div>

    {{-- Pagination --}}
    @if($overtimeRequests->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="small text-muted">
          Menampilkan {{ $overtimeRequests->firstItem() }}–{{ $overtimeRequests->lastItem() }}
          dari {{ $overtimeRequests->total() }} data
        </div>
        <div class="ml-auto">
          {{ $overtimeRequests->withQueryString()->links('pagination::bootstrap-4') }}
        </div>
      </div>
    @endif
  </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

<style>
  :root{ --hero-grad: linear-gradient(135deg,#0d6efd,#6f42c1); }

  /* Hero */
  .hero-card{ background:var(--hero-grad); color:#fff; border:0; border-radius:1.25rem; overflow:hidden; }
  .hero-body{ padding:1.1rem 1.25rem; }
  @media(min-width:768px){ .hero-body{ padding:1.35rem 1.5rem; } }
  .hero-title{ font-weight:700; letter-spacing:.2px; }
  .hero-meta{ opacity:.95; }
  .hero-actions{ display:flex; flex-wrap:wrap; gap:.5rem; }
  .btn-pill{ border-radius:999px!important; }
  .btn-elev{ box-shadow:0 10px 24px -12px rgba(13,110,253,.6); }

  /* Layout filter jadi horisontal & ringkas */
.filter-grid{
  display:flex; flex-wrap:wrap; gap:.75rem 1rem; align-items:end;
}
.filter-grid .control{ display:flex; flex-direction:column; }

/* Lebar compact untuk input/select tanggal & status */
.w-160{ width:160px; }
@media (min-width:992px){ .w-160{ width:180px; } }   /* sedikit lebih lega di desktop lebar */
@media (max-width:576px){ .w-160{ width:100%; } }     /* full di mobile */

/* (sudah ada) pill style dari versi sebelumnya tetap dipakai */


  /* Filter mini-hero */
  .filter-card{
    border:0; border-radius:1rem;
    background:
      linear-gradient(#ffffff,#ffffff) padding-box,
      linear-gradient(135deg, rgba(13,110,253,.25), rgba(111,66,193,.25)) border-box;
    border:1px solid transparent;
    box-shadow:0 10px 28px -20px rgba(13,110,253,.5);
  }
  .filter-card .card-body{ padding:.9rem 1rem; }
  @media(min-width:768px){ .filter-card .card-body{ padding:1rem 1.25rem; } }
  .filter-label{ font-size:.8rem; color:#6c757d; margin-bottom:.25rem }
  .pill .form-control, .pill .form-select{ border-radius:999px; height:44px; padding:0 .9rem; }
  .toolbar{ display:flex; gap:.5rem; }

  /* Table */
  .table-sticky thead th{ position:sticky; top:0; z-index:2; background:#fff; }
  .table-nowrap th, .table-nowrap td{ white-space:nowrap; }
  .table td, .table th{ vertical-align:middle; }

  /* KPI card */
  .card-kpi { border-left-width: .25rem !important; border-radius: .5rem; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Export Excel (encode params)
document.getElementById('downloadExcelBtn').addEventListener('click', function (e) {
  e.preventDefault();
  const startDate = document.getElementById('from_date').value || '';
  const endDate   = document.getElementById('to_date').value   || '';
  const url = `{{ route('admin.overtime-requests.export') }}?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
  window.location.href = url;
});

// Konfirmasi Approve/Reject (tanpa jQuery)
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.js-approve-form').forEach(form => {
    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      const res = await Swal.fire({
        title: 'Konfirmasi Persetujuan',
        text: 'Setujui pengajuan lembur ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, setujui',
        cancelButtonText: 'Batal'
      });
      if (res.isConfirmed) this.submit();
    });
  });

  document.querySelectorAll('.js-reject-form').forEach(form => {
    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      const res = await Swal.fire({
        title: 'Konfirmasi Penolakan',
        text: 'Yakin ingin menolak pengajuan lembur ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, tolak',
        cancelButtonText: 'Batal'
      });
      if (res.isConfirmed) this.submit();
    });
  });
});
</script>
@endpush
