@extends('layouts.master')
@section('title','Hak Cuti (Entitlements)')
@include('components.leave.styles-soft')

@section('content')
<div class="container-fluid">

  {{-- Flash --}}
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  {{-- HERO --}}
  <div class="card hero-card shadow-sm mb-4">
    <div class="hero-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
      <div>
        <h2 class="hero-title mb-1">Hak Cuti (Entitlements)</h2>
        <div class="hero-meta">
          Total: <strong>{{ $entitlements->total() }}</strong>
          <span class="d-none d-md-inline">•</span>
          <span class="d-block d-md-inline">
            Tampil: <strong>{{ $entitlements->count() }}</strong> item (halaman ini)
          </span>
        </div>
      </div>

      <div class="hero-actions mt-3 mt-md-0">
        <a href="{{ route('admin.leave-entitlements.generate.form') }}" class="btn btn-primary btn-pill btn-elev">
          <i class="fas fa-bolt mr-1"></i> Generate Entitlements
        </a>
        <a href="{{ route('admin.leave-entitlements.index') }}" class="btn btn-light btn-pill">
          Reset
        </a>
      </div>
    </div>
  </div>

  {{-- FILTER --}}
  <form method="GET" action="{{ request()->url() }}" class="card filter-card mb-3">
    <div class="card-body">
      <div class="filter-grid pill">
        <div class="row g-2 align-items-end w-100">
          <div class="col-md-5">
            <label class="filter-label">Cari</label>
            <input type="text" name="search" class="form-control"
                   placeholder="Nama / Nomor karyawan / Email…"
                   value="{{ request('search') }}">
          </div>
          <div class="col-md-3">
            <label class="filter-label">Jenis Cuti</label>
            <select name="leave_type_id" class="form-select">
              <option value="">Semua</option>
              @foreach($leaveTypes as $lt)
                <option value="{{ $lt->id }}" @selected(request('leave_type_id')==$lt->id)>{{ $lt->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="filter-label">Tahun</label>
            <input type="number" name="year" class="form-control" min="2000" max="2100"
                   value="{{ request('year', now()->year) }}">
          </div>
          <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-primary btn-pill w-100" type="submit">
              <i class="fas fa-search mr-1"></i> Cari
            </button>
            <a href="{{ request()->url() }}" class="btn btn-outline-secondary btn-pill">Reset</a>
          </div>
        </div>
      </div>
    </div>
  </form>

  {{-- TABLE --}}
  <div class="card shadow rounded-4">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered table-hover table-sticky align-middle w-100 table-nowrap mb-0">
          <thead class="thead-light">
            <tr>
              <th style="width:64px">No</th>
              <th>Karyawan</th>
              <th>Dept</th>
              <th>Jenis Cuti</th>
              <th>Periode</th>
              <th class="text-end">Opening</th>
              <th class="text-end">Accrued</th>
              <th class="text-end">Adjust</th>
              <th class="text-end">Used</th>
              <th class="text-end">Sisa</th>
              <th>Catatan</th>
              <th style="width:120px">Diperbarui</th>
            </tr>
          </thead>
          <tbody>
            @forelse($entitlements as $i => $en)
              @php
                $sisa = (float)($en->opening_balance ?? 0) + (float)($en->accrued ?? 0) + (float)($en->adjustments ?? 0) - (float)($en->used ?? 0);
              @endphp
              <tr>
                <td>{{ $entitlements->firstItem() + $i }}</td>
                <td class="fw-semibold">{{ $en->employee?->name ?? '—' }}</td>
                <td>{{ $en->employee?->department?->name ?? '—' }}</td>
                <td>{{ $en->leaveType?->name ?? '—' }}</td>
                <td>
                  {{ optional($en->period_start)->format('d M Y') }} —
                  {{ optional($en->period_end)->format('d M Y') }}
                </td>
                <td class="text-end">{{ number_format($en->opening_balance ?? 0, 2) }}</td>
                <td class="text-end">{{ number_format($en->accrued ?? 0, 2) }}</td>
                <td class="text-end">{{ number_format($en->adjustments ?? 0, 2) }}</td>
                <td class="text-end">{{ number_format($en->used ?? 0, 2) }}</td>
                <td class="text-end fw-bold">{{ number_format($sisa, 2) }}</td>
                <td>{{ $en->note ?? '—' }}</td>
                <td>{{ optional($en->updated_at)->format('d M Y H:i') }}</td>
              </tr>
            @empty
              <tr><td colspan="12" class="text-center py-4">Tidak ada data.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- FOOTER PAGINATION: sama seperti employee index --}}
    <div class="card-footer d-flex justify-content-between align-items-center">
      <div class="small text-muted">
        Menampilkan {{ $entitlements->firstItem() }}–{{ $entitlements->lastItem() }} dari {{ $entitlements->total() }} data
      </div>
      {{ $entitlements->withQueryString()->links('pagination::bootstrap-4') }}
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  /* (opsional) reuse style dari employee index yang kamu pakai */
  .table-sticky thead th{ position:sticky; top:0; z-index:2; background:#fff; }
  .table-nowrap th, .table-nowrap td{ white-space:nowrap; }
  .btn-pill{ border-radius:999px!important; }
  .hero-card{ background: linear-gradient(135deg,#0d6efd,#6f42c1); color:#fff; border:0; border-radius:1.25rem; }
  .hero-body{ padding:1.1rem 1.25rem; }
  .filter-card{
    border:0; border-radius:1rem;
    background:
      linear-gradient(#fff,#fff) padding-box,
      linear-gradient(135deg, rgba(13,110,253,.25), rgba(111,66,193,.25)) border-box;
    border:1px solid transparent;
    box-shadow:0 10px 28px -20px rgba(13,110,253,.5);
  }
</style>
@endpush
