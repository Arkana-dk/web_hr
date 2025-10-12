{{-- resources/views/admin/pages/work-schedules/confirmation.blade.php --}}
@extends('layouts.master')

@section('title', 'Konfirmasi Jadwal')

@push('styles')
<style>
  :root{ --soft-bg: rgba(13,110,253,.10); --soft-bd: rgba(13,110,253,.25); }
  .rounded-pill{ border-radius:999px!important; }
  .hero-card{ background: linear-gradient(135deg,#0d6efd,#6f42c1); color:#fff; border-radius:1rem; }
  .chip{ display:inline-flex; align-items:center; padding:.35rem .75rem; border-radius:999px; border:1px solid rgba(255,255,255,.35); font-weight:600; font-size:.85rem; }
  .table-sticky thead th{ background:#fff; position:sticky; top:0; z-index:1; }
  .pagination svg{ width:1rem; height:1rem; vertical-align:-.125em; }
  .pagination .hidden{ display:none!important; }

  /* 🔒 Matikan pseudo-element panah dari tema lama */
  .page-confirm-schedules .table-responsive::before,
  .page-confirm-schedules .table-responsive::after,
  .page-confirm-schedules .table-responsive > .table::before,
  .page-confirm-schedules .table-responsive > .table::after{
    content:none!important; display:none!important; background:none!important; box-shadow:none!important; border:0!important;
  }
</style>
@endpush

@section('content')
{{-- Fallback inline jika layout belum punya @stack('styles') --}}
<style>.page-confirm-schedules .table-responsive::before,.page-confirm-schedules .table-responsive::after,.page-confirm-schedules .table-responsive>.table::before,.page-confirm-schedules .table-responsive>.table::after{content:none!important;display:none!important}</style>

<div class="container py-4 page-confirm-schedules">

  {{-- HERO --}}
  <div class="card hero-card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
      <div class="mb-2">
        <h4 class="mb-1">📅 Konfirmasi Jadwal Kerja</h4>
        <small class="text-white-50">Tinjau sebelum menyimpan ke sistem.</small>
      </div>
      <div class="d-flex flex-wrap" style="gap:.5rem">
        <span class="chip">Total: {{ number_format($validSchedules->total()) }}</span>
        <span class="chip">Halaman ini: {{ $validSchedules->count() }}</span>
        <span class="chip">Page {{ $validSchedules->currentPage() }} / {{ $validSchedules->lastPage() }}</span>
      </div>
    </div>
  </div>

  @if (session('invalidEmployees') && count(session('invalidEmployees')))
    <div class="alert alert-warning">
      <strong>Perhatian:</strong> Ada karyawan tidak ditemukan. Silakan periksa kembali file Anda.
    </div>
  @endif

  {{-- TABEL --}}
  <div class="card shadow-sm">
    <div class="table-responsive" style="max-height:65vh;">
      <table class="table table-hover table-sticky align-middle mb-0">
        <thead>
          <tr>
            <th style="width:8%">#</th>
            <th style="width:18%">Nomor Karyawan</th>
            <th style="width:26%">Nama Karyawan</th>
            <th style="width:20%">Tanggal</th>
            <th>Shift</th>
          </tr>
        </thead>
        <tbody>
        @foreach($validSchedules as $i => $schedule)
          @php
            $shiftName = $schedule['shift_name'];
            $isHoliday = stripos($shiftName,'libur') !== false;
          @endphp
          <tr>
            <td>{{ $validSchedules->firstItem() + $i }}</td>
            <td>{{ $schedule['employee_number'] }}</td>
            <td>{{ $schedule['employee_name'] }}</td>
            <td>{{ $schedule['work_date'] }}</td>
            <td class="{{ $isHoliday ? 'text-danger font-weight-bold' : '' }}">{{ $shiftName }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>

    @if($validSchedules->hasPages())
      <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap">
        <small class="text-muted mb-2 mb-sm-0">
          Menampilkan {{ $validSchedules->firstItem() ?? 0 }}–{{ $validSchedules->lastItem() ?? 0 }} dari {{ $validSchedules->total() }} data
        </small>
      {{ $validSchedules->links('pagination::bootstrap-4') }}


      </div>
    @endif
  </div>

  {{-- ACTIONS --}}
  <form action="{{ route('admin.work-schedules.import.confirmed') }}" method="POST" class="mt-3">
    @csrf
    <div class="d-flex justify-content-between">
      <a href="{{ route('admin.work-schedules.index') }}" class="btn btn-outline-secondary">⬅ Kembali</a>
      <button type="submit" class="btn btn-success">✅ Simpan Jadwal</button>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@if(session('invalidEmployees') && count(session('invalidEmployees')) > 0)
<script>
  Swal.fire({
    icon: 'warning',
    title: 'Beberapa karyawan tidak ditemukan',
    html: `<ul class="text-start">@foreach(session('invalidEmployees') as $emp)<li>{{ $emp }}</li>@endforeach</ul>`,
    width: 600,
    confirmButtonText: 'Saya Mengerti'
  });
</script>
@endif
@endpush
