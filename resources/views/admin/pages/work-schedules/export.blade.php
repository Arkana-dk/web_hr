@extends('layouts.master')

@section('title', 'Export Template Jadwal Kerja')

@push('styles')
<style>
  .hero-card{
    background: linear-gradient(135deg,#0d6efd,#6f42c1);
    color:#fff;
    border-radius:1rem;
  }
  .hero-card .subtitle{ color: rgba(255,255,255,.75); }
  .rounded-pill{ border-radius:999px!important; }
</style>
@endpush

@section('content')
<div class="container py-4 page-export-schedules">

  {{-- HERO (BIRU) --}}
  <div class="card hero-card shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
      <div class="mb-2">
        <h4 class="mb-1">📤 Download Template Jadwal Kerja</h4>
        <small class="subtitle">Download template untuk upload jadwal work-schedule.</small>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('admin.work-schedules.index') }}" class="btn btn-light rounded-pill">⬅ Kembali</a>
      </div>
    </div>
  </div>

  {{-- HERO tetap seperti sebelumnya --}}

{{-- FORM (PUTIH) --}}
<div class="card shadow-sm">
  <div class="card-body">
    <form id="form-export-template" method="POST" action="{{ route('admin.workschedule.export.download') }}">
      @csrf
      <div class="row g-3">
        {{-- Department --}}
        <div class="col-md-4">
          <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
          <select id="department_id" name="department_id" class="form-select" required>
            <option value="">-- Pilih Department --</option>
            @foreach($departments as $d)
              <option value="{{ $d->id }}">{{ $d->name }}</option>
            @endforeach
          </select>
          <div class="form-text">Wajib. Memfilter Section & Position.</div>
        </div>

        {{-- Section (opsional, tergantung Department) --}}
        <div class="col-md-4">
          <label class="form-label fw-semibold">Section (opsional)</label>
          <select id="section_id" name="section_id" class="form-select" disabled>
            <option value="">-- Pilih Section --</option>
          </select>
          <div class="form-text">Kosongkan jika ingin semua section pada department.</div>
        </div>

        {{-- Position (opsional, tergantung Section) --}}
        <div class="col-md-4">
          <label class="form-label fw-semibold">Position (opsional)</label>
          <select id="position_id" name="position_id" class="form-select" disabled>
            <option value="">-- Pilih Position --</option>
          </select>
          <div class="form-text">Kosongkan jika ingin semua position pada filter di atas.</div>
        </div>

        {{-- Bulan --}}
        <div class="col-md-4">
          <label class="form-label fw-semibold">Bulan <span class="text-danger">*</span></label>
          <input type="month" name="month" class="form-control" required value="{{ $defaultMonth ?? '' }}">
        </div>
      </div>

      <div class="mt-4 d-flex gap-2">
        <button id="btn-download" class="btn btn-primary">
          <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
          <span class="label">Download Template</span>
        </button>
        <a href="{{ route('admin.work-schedules.index') }}" class="btn btn-outline-secondary">Kembali</a>
      </div>
    </form>
  </div>
</div>




  {{-- ALERTS --}}
  @if (session('error'))
    <div class="alert alert-danger mt-3">{{ session('error') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger mt-3">
      <ul class="mb-0">
        @foreach ($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

</div>
@endsection

@push('scripts')
<script>
(() => {
  const $dept = document.getElementById('department_id');
  const $sect = document.getElementById('section_id');
  const $pos  = document.getElementById('position_id');
  const $form = document.getElementById('form-export-template');
  const $btn  = document.getElementById('btn-download');

  function setLoading(selectEl, isLoading) {
    if (!selectEl) return;
    if (isLoading) {
      selectEl.innerHTML = '<option value="">Memuat...</option>';
      selectEl.disabled = true;
    } else {
      selectEl.disabled = false;
    }
  }

  function resetSelect(selectEl, placeholder) {
    if (!selectEl) return;
    selectEl.innerHTML = `<option value="">${placeholder}</option>`;
    selectEl.value = '';
    selectEl.disabled = true;
  }

  async function fetchJSON(url, params = {}) {
    const qs = new URLSearchParams(params).toString();
    const res = await fetch(url + (qs ? ('?' + qs) : ''));
    if (!res.ok) throw new Error('HTTP ' + res.status);
    return res.json();
  }

  // Saat Department berubah → muat Section
  $dept?.addEventListener('change', async () => {
    resetSelect($sect, '-- Pilih Section --');
    resetSelect($pos,  '-- Pilih Position --');

    const deptId = $dept.value;
    if (!deptId) return; // tidak ada dept, biarkan section/position disabled

    try {
      setLoading($sect, true);
      const data = await fetchJSON('{{ route('admin.sections.byDepartment') }}', { department_id: deptId });
      // Isi section
      let html = '<option value="">-- Pilih Section --</option>';
      (data.data || []).forEach(s => {
        html += `<option value="${s.id}">${s.name}</option>`;
      });
      $sect.innerHTML = html;
      $sect.disabled = false;
    } catch (e) {
      console.error(e);
      resetSelect($sect, '-- Pilih Section --');
      alert('Gagal memuat Section. Coba lagi.');
    }
  });

  // Saat Section berubah → muat Position
  $sect?.addEventListener('change', async () => {
    resetSelect($pos, '-- Pilih Position --');

    const sectionId = $sect.value;
    if (!sectionId) return; // tidak pilih section → biarkan kosong & disabled

    try {
      setLoading($pos, true);
      const data = await fetchJSON('{{ route('admin.positions.bySections') }}', { 'section_ids[]': sectionId });
      // Isi position
      let html = '<option value="">-- Pilih Position --</option>';
      (data.data || []).forEach(p => {
        html += `<option value="${p.id}">${p.name}</option>`;
      });
      $pos.innerHTML = html;
      $pos.disabled = false;
    } catch (e) {
      console.error(e);
      resetSelect($pos, '-- Pilih Position --');
      alert('Gagal memuat Position. Coba lagi.');
    }
  });

  // UX: disable tombol saat submit
  $form?.addEventListener('submit', () => {
    $btn.disabled = true;
    $btn.querySelector('.spinner-border')?.classList.remove('d-none');
    $btn.querySelector('.label').textContent = 'Menyiapkan...';
  });
})();
</script>
@endpush