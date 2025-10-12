@extends('layouts.master')

@section('title', 'Import Jadwal Kerja')

@push('styles')
<style>
  :root{ --soft-bg: rgba(13,110,253,.10); --soft-bd: rgba(13,110,253,.25); }
  .rounded-pill{ border-radius:999px!important; }
  .hero-card{ background: linear-gradient(135deg,#0d6efd,#6f42c1); color:#fff; border-radius:1rem; }
  .chip{ display:inline-flex; align-items:center; padding:.35rem .75rem; border-radius:999px; border:1px solid rgba(255,255,255,.35); font-weight:600; font-size:.85rem; }
</style>
@endpush

@section('content')
<div class="container py-4 page-import-schedules">

  {{-- HERO --}}
  <div class="card hero-card shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
      <div class="mb-2">
        <h4 class="mb-1">📤 Import Jadwal Kerja</h4>
        <small class="text-white-50">
          Upload file Excel jadwal kerja lalu tinjau datanya sebelum disimpan.
        </small>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.work-schedules.index') }}" class="btn btn-light rounded-pill">⬅ Kembali</a>
      </div>
    </div>
  </div>

  {{-- ALERTS --}}
  @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- FORM UPLOAD --}}
  <div class="card shadow-sm">
    <div class="card-body">
      <form id="form-import" action="{{ route('admin.work-schedules.import') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
          <label for="file" class="form-label fw-bold">Pilih File Excel</label>
          <input
            type="file"
            name="file"
            id="file"
            class="form-control"
            accept=".xls,.xlsx"
            required
          >
          <div class="form-text">
            • Format: .xls / .xlsx &nbsp;• Sheet 1 = mapping shift (Kode | Nama | In | Out) &nbsp;• Sheet 2..N = nama bulan, contoh: <em>JANUARI-2025</em> / <em>JANUARY-2025</em><br>
            • Kolom jadwal: NIK | Nama | 1 | 2 | ... | 31
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <a href="{{ route('admin.work-schedules.index') }}" class="btn btn-outline-secondary rounded-pill">Batal</a>
          <button id="btn-submit" type="submit" class="btn btn-primary rounded-pill">
            <span class="label">📤 Upload & Preview</span>
            <span class="spinner-border spinner-border-sm ms-1 d-none" role="status" aria-hidden="true"></span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Cegah double submit & tampilkan spinner
  const form = document.getElementById('form-import');
  const btn  = document.getElementById('btn-submit');
  form.addEventListener('submit', function () {
    btn.disabled = true;
    btn.querySelector('.spinner-border').classList.remove('d-none');
    btn.querySelector('.label').textContent = 'Mengunggah...';
  });
</script>
@endpush
