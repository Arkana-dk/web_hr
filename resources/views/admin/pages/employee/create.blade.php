@extends('layouts.master')

@section('title','Tambah Karyawan')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
  /* ========== Utilities ==========*/
  .btn-pill{ border-radius: 999px; }
  .btn-elev{ box-shadow: 0 8px 24px -10px rgba(13,110,253,.6); }
  .btn-soft-primary{ background: rgba(13,110,253,.08); border-color: rgba(13,110,253,.2); color:#0d6efd; }
  .btn-soft-primary:hover{ background: rgba(13,110,253,.12); }
  .btn-soft-white{ background: rgba(255,255,255,.15); border-color: rgba(255,255,255,.25); color: #fff; }
  .btn-soft-white:hover{ background: rgba(255,255,255,.25); color:#fff; }
  .btn-pill.btn-sm{ padding:.35rem .7rem; line-height:1; }

  :root{
    --hero-grad: linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%);
    --chip-bg: rgba(13,110,253,.08);
    --chip-bd: rgba(13,110,253,.2);
  }

  /* ========= HERO ========= */
  .hero-card{ background: var(--hero-grad); color: #fff; border:0; border-radius: 1.25rem; overflow:hidden; }
  .hero-card .hero-body{ padding: 1.25rem 1.25rem; }
  @media (min-width: 768px){ .hero-card .hero-body{ padding: 1.75rem 1.75rem; } }
  .hero-title{ font-weight:700; letter-spacing:.2px; }
  .hero-meta{ opacity:.95 }
  .hero-badges{ display:flex; flex-wrap:wrap; gap:.5rem; }
  .tag-soft{ background: rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.28); color:#fff; padding:.35rem .6rem; border-radius:999px; font-size:.78rem; font-weight:600; white-space:nowrap; }

  /* ========= Summary ========= */
  .stat-card{ border: 1px solid rgba(0,0,0,.06); border-radius: 1rem; }
  .summary-grid{ display:grid; grid-template-columns:1fr; gap:.75rem }
  @media (min-width:576px){ .summary-grid{ grid-template-columns:repeat(3,minmax(0,1fr)); } }
  .summary-item{ background:#f8f9fa; border-radius:.9rem; padding:.8rem 1rem; }
  .summary-item .label{ font-size:.8rem; color:#6c757d; margin-bottom:.25rem }
  .summary-item .value{ font-weight:700; font-variant-numeric: tabular-nums; text-align:right }

  /* ========= Table ========= */
  .table-sticky thead th{ position:sticky; top:0; background:#fff; z-index:2; }
  .status-badge{ text-transform:uppercase; letter-spacing:.02em; padding:.35rem .6rem; border-radius:999px; line-height:1; }
  .table thead th{ border-top:0; }
  .table-hover tbody tr{ transition: background-color .12s ease; }

  /* ========= Filter (mini-hero) ========= */
  .filter-card{
    border:0; border-radius:1.25rem;
    background:
      linear-gradient(#ffffff,#ffffff) padding-box,
      linear-gradient(135deg, rgba(13,110,253,.35), rgba(111,66,193,.35)) border-box;
    border: 1px solid transparent;
    box-shadow: 0 10px 30px -18px rgba(13,110,253,.45);
  }
  .filter-card .card-body{ padding: .9rem 1rem; }
  @media (min-width: 768px){ .filter-card .card-body{ padding: 1.05rem 1.25rem; } }

  /* ========= Form Pill Controls ========= */
  .pill .form-control,
  .pill .form-select{
    border-radius: 999px; height: 44px; padding-left: 14px; padding-right: 14px;
  }
  .pill .form-select{ padding-right: 36px; }

  /* Tooltip icon agar label tidak tinggi */
  .label-actions{ display:flex; align-items:center; gap:.5rem; }
  .label-actions .info{ color:#6c757d; }
  .label-actions .info:hover{ color:#0d6efd; }

  /* ========= Grid untuk alignment rapi ========= */
  .grid-3{ display:grid; grid-template-columns: 1fr; gap: 1rem; }
  .grid-2{ display:grid; grid-template-columns: 1fr; gap: 1rem; }
  @media (min-width: 768px){
    .grid-3{ grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .grid-2{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
  }

  /* ========= Select2 (Bootstrap 5 theme) ========= */
  .select2-container{ width:100% !important; }
  .select2-container--bootstrap-5 .select2-selection--single{
    height:44px !important; padding: 0.375rem 1rem !important; border-radius:999px !important;
    display:flex; align-items:center; border:1px solid #ced4da; background:#fff;
  }
  .select2-container--bootstrap-5 .select2-selection__rendered{ padding-left:0 !important; line-height:normal !important; font-size:0.9375rem; color:#212529; }
  .select2-container--bootstrap-5 .select2-selection__arrow{ height:44px !important; top:0 !important; right:10px; }
  .select2-container--bootstrap-5.select2-container--focus .select2-selection{ box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25); border-color:#86b7fe; }
</style>
@endpush

@section('content')
<div class="container-fluid">

  {{-- HeroCard --}}
  <div class="hero-card mb-4 shadow-sm">
    <div class="hero-body d-flex justify-content-between align-items-center flex-wrap">
      <div class="mb-2 mb-md-0">
        <h2 class="hero-title mb-1">Tambah Karyawan</h2>
        <p class="hero-meta mb-0 small">Formulir pembuatan akun karyawan lengkap dengan data payroll dan personal.</p>
      </div>
      <a href="{{ route('admin.employees.index') }}" class="btn btn-soft-white btn-sm btn-pill">
        <i class="fas fa-arrow-left me-1"></i> Kembali
      </a>
    </div>
  </div>

  {{-- Alerts --}}
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- FORM --}}
  <form action="{{ route('admin.employees.store') }}" method="POST" enctype="multipart/form-data" class="mb-5">
    @csrf

    {{-- Akun Login --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-header bg-light border-0 py-3 rounded-top-4">
        <h6 class="m-0 fw-bold text-primary">
          <i class="fas fa-user-lock me-2"></i> Akun Login (Dibuat Otomatis)
        </h6>
      </div>
      <div class="card-body pt-0 pill">
        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" name="password" id="password" class="form-control" required minlength="6" autocomplete="new-password">
              <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Tampilkan password">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <div class="form-text small text-muted">Minimal 6 karakter. Klik ikon untuk lihat/sembunyi.</div>
          </div>
        </div>
      </div>
    </div>

    {{-- Data Pribadi --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-header bg-light border-0 py-3 rounded-top-4">
        <h6 class="m-0 fw-bold text-primary">
          <i class="fas fa-id-badge me-2"></i> Data Pribadi
        </h6>
      </div>
      <div class="card-body pt-0 pill">
        <div class="row g-4">
          {{-- Baris 1 --}}
          <div class="col-md-6">
            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Nomor Induk Kewarganegaraan <span class="text-danger">*</span></label>
            <input type="number" name="national_identity_number" class="form-control" value="{{ old('national_identity_number') }}" required>
          </div>

          {{-- Baris 2 --}}
          <div class="col-md-6">
            <label class="form-label">Telepon <span class="text-danger">*</span></label>
            <input type="number" name="phone" class="form-control" value="{{ old('phone') }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Agama <span class="text-danger">*</span></label>
            <select name="religion" class="form-select select2" required>
              <option value="">-- Pilih Agama --</option>
              @foreach(['ISLAM','KRISTEN','KATHOLIK','HINDU','BUDDHA','KONG HU CHU'] as $r)
                <option value="{{ $r }}" @selected(old('religion')==$r)>{{ $r }}</option>
              @endforeach
            </select>
          </div>

          {{-- Baris 3 --}}
          <div class="col-md-6">
            <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
            <select name="gender" class="form-select select2" required>
              <option value="">-- Pilih Gender --</option>
              <option value="Laki-laki" @selected(old('gender')=='Laki-laki')>Laki-laki</option>
              <option value="Perempuan" @selected(old('gender')=='Perempuan')>Perempuan</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Status Perkawinan <span class="text-danger">*</span></label>
            <select name="marital_status" class="form-select select2" required>
              <option value="">-- Pilih Status --</option>
              <option value="Sudah Kawin" @selected(old('marital_status')=='Sudah Kawin')>Sudah Kawin</option>
              <option value="Belum Kawin" @selected(old('marital_status')=='Belum Kawin')>Belum Kawin</option>
            </select>
          </div>

          {{-- Baris 4 --}}
          <div class="col-md-6">
            <label class="form-label">Tempat Lahir <span class="text-danger">*</span></label>
            <input type="text" name="place_of_birth" class="form-control" value="{{ old('place_of_birth') }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
            <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}" required>
          </div>

          {{-- Baris 5 --}}
          <div class="col-md-6">
            <label class="form-label">No. Kartu Keluarga <span class="text-danger">*</span></label>
            <input type="number" name="family_number_card" class="form-control" value="{{ old('family_number_card') }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Pendidikan <span class="text-danger">*</span></label>
            <input type="text" name="education" class="form-control" value="{{ old('education') }}" required>
          </div>

          <div class="col-12">
            <label class="form-label">Alamat <span class="text-danger">*</span></label>
            <textarea name="address" class="form-control" rows="3" required>{{ old('address') }}</textarea>
          </div>
        </div>
      </div>
    </div>

    {{-- Kepegawaian & Payroll (RAPIH) --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-header bg-light border-0 py-3 rounded-top-4">
        <h6 class="m-0 fw-bold text-primary">
          <i class="fas fa-briefcase me-2"></i> Data Kepegawaian & Payroll
        </h6>
      </div>
      <div class="card-body pt-0 pill">
        {{-- Row 1: 3 kolom sejajar --}}
        <div class="grid-3">
          <div>
            <label class="form-label d-flex justify-content-between align-items-center">
              <span>TMT (Mulai Tugas) <span class="text-danger">*</span></span>
              <button type="button" class="btn btn-sm btn-soft-primary btn-pill" id="set-this-year">This Year</button>
            </label>
            <input type="date" name="tmt" id="tmt" class="form-control" value="{{ old('tmt') }}" required>
          </div>
          <div>
            <label class="form-label">Tanggal Akhir Kontrak <span class="text-danger">*</span></label>
            <input type="date" name="contract_end_date" id="contract_end_date" class="form-control" value="{{ old('contract_end_date') }}" required>
          </div>
          <div>
            <label class="form-label d-flex justify-content-between align-items-center">
              <span>Gaji Pokok <span class="text-danger">*</span></span>
              <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none label-actions" data-bs-toggle="tooltip" title="Payroll akan membaca komponen BASIC jika tersedia.">
                <i class="fas fa-info-circle info"></i>
              </button>
            </label>
            <input type="number" name="salary" class="form-control" value="{{ old('salary') }}" required>
          </div>
        </div>

        {{-- Row 2: 3 kolom sejajar --}}
        <div class="grid-3 mt-3">
          <div>
            <label class="form-label d-flex justify-content-between align-items-center">
              <span>Jumlah Tanggungan <span class="text-danger">*</span></span>
              <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none label-actions" data-bs-toggle="tooltip" title="Jumlah anggota keluarga yang ditanggung.">
                <i class="fas fa-info-circle info"></i>
              </button>
            </label>
            <input type="number" name="dependents_count" class="form-control" value="{{ old('dependents_count') }}" required>
          </div>

          <div>
            <label class="form-label">Department <span class="text-danger">*</span></label>
            <select id="department-select" name="department_id" class="form-select select2" required>
              <option value="">-- Pilih Department --</option>
              @foreach($departments as $dept)
                <option value="{{ $dept->id }}" @selected(old('department_id')==$dept->id)>{{ $dept->name }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="form-label">Position <span class="text-danger">*</span></label>
            <select id="position-select" name="position_id" class="form-select select2" required>
              <option value="">-- Pilih Position --</option>
            </select>
          </div>
        </div>

        {{-- Row 3: 2 kolom sejajar --}}
        <div class="grid-2 mt-3">
          <div>
            <label class="form-label">Section <span class="text-danger">*</span></label>
            <select name="section_id" class="form-select select2" required>
              <option value="">-- Pilih Seksi --</option>
              @foreach($sections as $section)
                <option value="{{ $section->id }}" @selected(old('section_id')==$section->id)>{{ $section->name }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="form-label">Grup <span class="text-danger">*</span></label>
            <select name="group_id" class="form-select select2" required>
              <option value="">-- Pilih Grup --</option>
              @foreach($groups as $group)
                <option value="{{ $group->id }}" @selected(old('group_id')==$group->id)>{{ $group->name }}</option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- Row 4: 2 kolom sejajar --}}
        <div class="grid-2 mt-3">
          <div>
            <label class="form-label d-flex justify-content-between align-items-center">
              <span>Pay Group (Payroll)</span>
              <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none label-actions" data-bs-toggle="tooltip" title="Digunakan dalam Pay Run dan rate komponen payroll.">
                <i class="fas fa-info-circle info"></i>
              </button>
            </label>
            <select name="pay_group_id" class="form-select select2">
              <option value="">— None —</option>
              @foreach($payGroups as $pg)
                <option value="{{ $pg->id }}" @selected(old('pay_group_id')==$pg->id)>{{ $pg->code }} — {{ $pg->name }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="form-label">Nomor Karyawan</label>
            <input type="text" name="employee_number" class="form-control" value="{{ old('employee_number') }}" readonly>
            <div class="form-text small text-muted">Terisi otomatis setelah disimpan.</div>
          </div>
        </div>
      </div>
    </div>

    {{-- Bank & Dokumen --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-header bg-light border-0 py-3 rounded-top-4">
        <h6 class="m-0 fw-bold text-primary">
          <i class="fas fa-piggy-bank me-2"></i> Data Bank & Dokumen
        </h6>
      </div>
      <div class="card-body pt-0 pill"> {{-- fix: pt-0a -> pt-0 --}}
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nama Rekening <span class="text-danger">*</span></label>
            <input type="text" name="bank_account_name" class="form-control" value="{{ old('bank_account_name') }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Nomor Rekening <span class="text-danger">*</span></label>
            <input type="text" name="bank_account_number" class="form-control" value="{{ old('bank_account_number') }}" required>
          </div>
          <div class="col-md-12">
            <label class="form-label">Upload Photo</label>
            <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png">
            <img id="preview-photo" class="img-thumbnail mt-2 d-none" style="max-width: 200px;">
          </div>
        </div>
      </div>
    </div>

    {{-- Tombol --}}
    <div class="text-end">
      <button type="submit" class="btn btn-primary btn-lg btn-pill btn-elev px-4">
        <i class="fas fa-save me-2"></i> Simpan Karyawan
      </button>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Select2 init
    $('.select2').select2({ theme: 'bootstrap-5', placeholder: '-- Pilih --', allowClear: true, width: '100%' });

    // Bootstrap tooltips
    if (window.bootstrap && bootstrap.Tooltip) {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    }

    // Isi otomatis tahun kontrak
    const btn = document.getElementById('set-this-year');
    const tmt = document.getElementById('tmt');
    const end = document.getElementById('contract_end_date');
    btn?.addEventListener('click', () => {
      const year = new Date().getFullYear();
      tmt.value = `${year}-01-01`;
      end.value = `${year}-12-31`;
    });

    // Toggle password visibility
    const pwd = document.getElementById('password');
    const toggle = document.getElementById('togglePassword');
    toggle?.addEventListener('click', () => {
      const showing = pwd.type === 'text';
      pwd.type = showing ? 'password' : 'text';
      toggle.innerHTML = showing ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });

    // Department → Position dropdown (refresh Select2 dengan benar)
    const deptPositions = @json($deptPositions);
    const $deptSel = $('#department-select');
    const $posSel  = $('#position-select');
    const oldDept = "{{ old('department_id') }}";
    const oldPos  = "{{ old('position_id') }}";

    function fill(deptId, selPos){
      // reset except first placeholder
      $posSel.find('option').not(':first').remove();
      (deptPositions[deptId] || []).forEach(p => {
        const opt = new Option(p.name, p.id, false, false);
        $posSel.append(opt);
      });
      if (selPos) $posSel.val(String(selPos));
      $posSel.trigger('change.select2');
    }

    if (oldDept) fill(oldDept, oldPos);
    $deptSel.on('change', e => fill(e.target.value, ''));

    // Preview foto upload
    const input = document.querySelector('input[name="photo"]');
    const preview = document.getElementById('preview-photo');
    input?.addEventListener('change', (event) => {
      const file = event.target.files?.[0];
      if (!file) return preview.classList.add('d-none');
      const reader = new FileReader();
      reader.onload = (e) => { preview.src = e.target.result; preview.classList.remove('d-none'); };
      reader.readAsDataURL(file);
    });
  });
</script>
@endpush
