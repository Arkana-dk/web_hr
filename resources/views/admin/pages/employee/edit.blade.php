{{-- resources/views/admin/pages/employee/edit.blade.php --}}
@extends('layouts.master')

@section('title','Edit Karyawan')

@push('styles')
<style>
  :root{ --hero-grad: linear-gradient(135deg,#0d6efd,#6f42c1); } 

  /* HERO */
  .hero-card{ background:var(--hero-grad); color:#fff; border:0; border-radius:1.25rem; overflow:hidden; box-shadow:0 12px 28px -18px rgba(13,110,253,.55); }
  .hero-body{ padding:1.2rem 1.35rem; } @media(min-width:768px){ .hero-body{ padding:1.6rem 1.75rem; } }
  .avatar-lg{ width:88px; height:88px; border-radius:50%; overflow:hidden; border:2px solid rgba(255,255,255,.6); }
  .avatar-lg img{ width:100%; height:100%; object-fit:cover; }
  .btn-pill{ border-radius:999px!important; }
  .btn-soft-white{ background:rgba(255,255,255,.15); border-color:rgba(255,255,255,.25); color:#fff; }
  .btn-soft-white:hover{ background:rgba(255,255,255,.25); color:#fff; }
  .chip{ display:inline-flex; align-items:center; gap:.4rem; padding:.35rem .6rem; border-radius:999px; background: rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.28); font-weight:600; font-size:.8rem; color:#fff; }

  /* CARDS */
  .card.soft{
    border:0; border-radius:1rem;
    background: linear-gradient(#fff,#fff) padding-box,
               linear-gradient(135deg, rgba(13,110,253,.18), rgba(111,66,193,.18)) border-box;
    border:1px solid transparent;
    box-shadow:0 12px 30px -20px rgba(13,110,253,.35);
  }
  .card.soft .card-header{ background:#fff; border-bottom:1px solid rgba(0,0,0,.06); border-top-left-radius:1rem; border-top-right-radius:1rem; font-weight:600; }

  .form-required::after{ content:" *"; color:#dc3545; }
  .form-text{ font-size:.8rem; color:#6c757d; }

  /* Sticky Save Bar */
  .savebar{
    position:sticky; bottom:0; z-index:10; background:#fff;
    border-top:1px solid rgba(0,0,0,.08); padding:.75rem; border-bottom-left-radius:1rem; border-bottom-right-radius:1rem;
    display:flex; gap:.5rem; justify-content:flex-end;
  }

  /* Helpers */
  .grid-2{ display:grid; gap:.75rem; grid-template-columns:1fr; }
  @media(min-width:992px){ .grid-2{ grid-template-columns:1fr 1fr; } }
</style>
@endpush

@section('content')
<div class="container-fluid">

  {{-- Alerts --}}
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
  @endif

  {{-- ================= HERO ================= --}}
  <div class="card hero-card mb-4">
    <div class="hero-body d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-3">
        <div class="avatar-lg">
          @if($employee->photo)
            <img src="{{ asset('storage/'.$employee->photo) }}" alt="Foto {{ $employee->name }}">
          @else
            <img src="{{ asset('images/default-user.png') }}" alt="Foto default">
          @endif
        </div>
        <div>
          <div class="h3 mb-1">{{ $employee->name }}</div>
          <div class="d-flex flex-wrap gap-2">
            <span class="chip"><i class="fas fa-id-badge"></i> {{ $employee->employee_number ?? '—' }}</span>
            @if($employee->position?->name)<span class="chip"><i class="fas fa-briefcase"></i> {{ $employee->position->name }}</span>@endif
            @if($employee->department?->name)<span class="chip"><i class="fas fa-building"></i> {{ $employee->department->name }}</span>@endif
          </div>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.employees.index') }}" class="btn btn-soft-white btn-pill">
          <i class="fas fa-arrow-left"></i> Kembali
        </a>
        <a href="{{ route('admin.employees.show', $employee) }}" class="btn btn-light btn-pill">
          <i class="fas fa-eye"></i> Lihat
        </a>
      </div>
    </div>
  </div>

  {{-- ================= FORM ================= --}}
  <form action="{{ route('admin.employees.update', $employee) }}" method="POST" enctype="multipart/form-data" id="empForm">
    @csrf @method('PUT')

    <div class="grid-2">

      {{-- ==== Data Pribadi ==== --}}
      <div class="card soft">
        <div class="card-header"><i class="fas fa-id-badge me-2 text-primary"></i> Data Pribadi</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label form-required">Nama Lengkap</label>
              <input type="text" name="name" class="form-control" value="{{ old('name', $employee->name) }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label form-required">Email</label>
              <input type="email" name="email" class="form-control" value="{{ old('email', $employee->email) }}" required>
            </div>

            <div class="col-md-6">
              <label class="form-label form-required">Nomor Induk Kependudukan (NIK)</label>
              <input type="text" name="national_identity_number" class="form-control" value="{{ old('national_identity_number', $employee->national_identity_number) }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label form-required">No. Kartu Keluarga</label>
              <input type="text" name="family_number_card" class="form-control" value="{{ old('family_number_card', $employee->family_number_card) }}" required>
            </div>

            <div class="col-md-6">
              <label class="form-label form-required">Telepon</label>
              <input type="text" name="phone" class="form-control" value="{{ old('phone', $employee->phone) }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label form-required">Agama</label>
              <select name="religion" class="form-select" required>
                @foreach(['ISLAM','KRISTEN','KATHOLIK','HINDU','BUDDHA','KONG HU CHU'] as $r)
                  <option value="{{ $r }}" @selected(old('religion', $employee->religion) == $r)>{{ $r }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label form-required">Jenis Kelamin</label>
              <select name="gender" class="form-select" required>
                <option value="Laki-laki"  @selected(old('gender', $employee->gender) == 'Laki-laki')>Laki-laki</option>
                <option value="Perempuan"  @selected(old('gender', $employee->gender) == 'Perempuan')>Perempuan</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label form-required">Status Perkawinan</label>
              <select name="marital_status" class="form-select" required>
                <option value="Sudah Kawin" @selected(old('marital_status', $employee->marital_status) == 'Sudah Kawin')>Sudah Kawin</option>
                <option value="Belum Kawin" @selected(old('marital_status', $employee->marital_status) == 'Belum Kawin')>Belum Kawin</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label form-required">Tempat Lahir</label>
              <input type="text" name="place_of_birth" class="form-control" value="{{ old('place_of_birth', $employee->place_of_birth) }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label form-required">Tanggal Lahir</label>
              <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $employee->date_of_birth ? \Carbon\Carbon::parse($employee->date_of_birth)->format('Y-m-d') : '') }}" required>
            </div>

            <div class="col-md-6">
              <label class="form-label form-required">Pendidikan</label>
              <input type="text" name="education" class="form-control" value="{{ old('education', $employee->education) }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Jumlah Tanggungan</label>
              <input type="number" min="0" name="dependents_count" class="form-control" value="{{ old('dependents_count', $employee->dependents_count) }}">
            </div>

            <div class="col-12">
              <label class="form-label form-required">Alamat</label>
              <textarea name="address" class="form-control" rows="3" required oninput="document.getElementById('addrCount').textContent=this.value.length">{{ old('address', $employee->address) }}</textarea>
              <div class="form-text"><span id="addrCount">{{ strlen(old('address', $employee->address ?? '')) }}</span> karakter</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Title (Job Title)</label>
              <input type="text" name="title" class="form-control" value="{{ old('title', $employee->title) }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Role Akses</label>
              <select name="role" class="form-select">
                @foreach(['employee','admin','superadmin','developer'] as $role)
                  <option value="{{ $role }}" @selected(old('role', $employee->role) == $role)>{{ ucfirst($role) }}</option>
                @endforeach
              </select>
              <div class="form-text">Mengatur hak akses aplikasi (bila model Employee memegang kolom role).</div>
            </div>

            <div class="col-12">
              <label class="form-label">Upload Photo</label>
              <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png">
              <div class="mt-2 d-flex align-items-center gap-3">
                @if($employee->photo)
                  <img src="{{ asset('storage/'.$employee->photo) }}" class="rounded" style="max-width:150px;">
                @endif
                <img id="preview-photo" class="img-thumbnail d-none" style="max-width:150px;">
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- ==== Kepegawaian & Payroll ==== --}}
      <div class="card soft">
        <div class="card-header"><i class="fas fa-briefcase me-2 text-primary"></i> Kepegawaian & Payroll</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label form-required">TMT (Mulai Tugas)</label>
              <input type="date" name="tmt" id="tmt" class="form-control" value="{{ old('tmt', $employee->tmt ? \Carbon\Carbon::parse($employee->tmt)->format('Y-m-d') : '') }}" required>
            </div>
            <div class="col-md-4">
              <label class="form-label form-required">Akhir Kontrak</label>
              <input type="date" name="contract_end_date" id="contract_end_date" class="form-control" value="{{ old('contract_end_date', $employee->contract_end_date ? \Carbon\Carbon::parse($employee->contract_end_date)->format('Y-m-d') : '') }}" required>
              <div class="form-text" id="contractHelp"></div>
            </div>
            <div class="col-md-4">
              <label class="form-label form-required">Gaji Pokok</label>
              <input type="number" name="salary" id="salary" class="form-control" value="{{ old('salary', $employee->salary) }}" required>
              <div class="form-text">Preview: <strong id="salaryPreview">Rp 0</strong></div>
            </div>

            <div class="col-md-6">
              <label class="form-label form-required">Department</label>
              <select id="department-select" name="department_id" class="form-select" required>
                @foreach($departments as $dept)
                  <option value="{{ $dept->id }}" @selected(old('department_id', $employee->department_id) == $dept->id)>{{ $dept->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label form-required">Position</label>
              <select id="position-select" name="position_id" class="form-select" required>
                <option value="">-- Pilih Position --</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label form-required">Section</label>
              <select id="section-select" name="section_id" class="form-select" required>
                <option value="">-- Pilih Seksi --</option>
                @foreach($sections as $section)
                  <option value="{{ $section->id }}" @selected(old('section_id', $employee->section_id) == $section->id)>{{ $section->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label form-required">Grup</label>
              <select id="group-select" name="group_id" class="form-select" required>
                <option value="">-- Pilih Grup --</option>
                @foreach($groups as $group)
                  <option value="{{ $group->id }}" @selected(old('group_id', $employee->group_id) == $group->id)>{{ $group->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Pay Group (Payroll)</label>
              <select name="pay_group_id" class="form-select">
                <option value="">— None —</option>
                @foreach($payGroups as $pg)
                  <option value="{{ $pg->id }}" @selected(old('pay_group_id', $employee->pay_group_id) == $pg->id)>{{ $pg->code }} — {{ $pg->name }}</option>
                @endforeach
              </select>
              <div class="form-text">Dipakai untuk Pay Run & komponen/rate payroll (bukan grup organisasi).</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Nomor Karyawan</label>
              <input type="text" name="employee_number" class="form-control" value="{{ old('employee_number', $employee->employee_number) }}" readonly>
            </div>
          </div>
        </div>
      </div>

      {{-- ==== Bank & Dokumen ==== --}}
      <div class="card soft">
        <div class="card-header"><i class="fas fa-piggy-bank me-2 text-primary"></i> Bank & Dokumen</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label form-required">Nama Bank</label>
              <input type="text" name="bank_name" class="form-control" list="bankList" value="{{ old('bank_name', $employee->bank_name) }}" required>
              <datalist id="bankList">
                <option>BCA</option><option>Mandiri</option><option>BRI</option><option>BNI</option>
                <option>CIMB Niaga</option><option>BTN</option><option>Permata</option><option>Danamon</option>
                <option>BSI</option>
              </datalist>
            </div>
            <div class="col-md-6">
              <label class="form-label form-required">Nama Rekening</label>
              <input type="text" name="bank_account_name" class="form-control" value="{{ old('bank_account_name', $employee->bank_account_name) }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label form-required">Nomor Rekening</label>
              <input type="text" name="bank_account_number" class="form-control" value="{{ old('bank_account_number', $employee->bank_account_number) }}" required>
            </div>
          </div>
        </div>

        {{-- Sticky Save Bar --}}
        <div class="savebar">
          <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary btn-pill">Batal</a>
          <button type="submit" class="btn btn-primary btn-pill">
            <i class="fas fa-save me-1"></i> Simpan Perubahan
          </button>
        </div>
      </div>

    </div> {{-- /grid-2 --}}
  </form>
</div>
@endsection

<!-- Pastikan Bootstrap Icons sudah dimuat -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<script>
document.addEventListener('DOMContentLoaded', function () {
  const toggle = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');
  const icon = toggle.querySelector('i');

  toggle.addEventListener('click', function () {
    const isHidden = passwordInput.type === 'password';
    passwordInput.type = isHidden ? 'text' : 'password';
    icon.classList.toggle('bi-eye');
    icon.classList.toggle('bi-eye-slash');
  });
});
</script>

@push('scripts')
<script>
  // ======== Dept -> Positions map dari controller ========
  const deptPositions = @json($deptPositions);

  document.addEventListener('DOMContentLoaded', () => {
    const deptSel = document.getElementById('department-select');
    const posSel  = document.getElementById('position-select');
    const oldDept = "{{ old('department_id', $employee->department_id) }}";
    const oldPos  = "{{ old('position_id', $employee->position_id) }}";

    function fillPositions(deptId, selected){
      posSel.innerHTML = '<option value="">-- Pilih Position --</option>';
      (deptPositions[deptId] || []).forEach(p => {
        const opt = new Option(p.name, p.id);
        if (String(p.id) === String(selected)) opt.selected = true;
        posSel.add(opt);
      });
    }
    if (oldDept) fillPositions(oldDept, oldPos);
    deptSel?.addEventListener('change', e => fillPositions(e.target.value, ''));
  });

  // ======== Preview Photo ========
  (function(){
    const input = document.querySelector('input[name="photo"]');
    const preview = document.getElementById('preview-photo');
    input?.addEventListener('change', (e) => {
      const file = e.target.files?.[0];
      if (!file) { preview.classList.add('d-none'); preview.src = '#'; return; }
      const reader = new FileReader();
      reader.onload = (evt) => { preview.src = evt.target.result; preview.classList.remove('d-none'); };
      reader.readAsDataURL(file);
    });
  })();

  // ======== Salary Preview Rp ========
  (function(){
    const inp = document.getElementById('salary');
    const out = document.getElementById('salaryPreview');
    function fmt(){
      const n = Number(inp.value||0);
      out.textContent = (isNaN(n) ? 'Rp 0' : 'Rp ' + n.toLocaleString('id-ID'));
    }
    inp?.addEventListener('input', fmt); fmt();
  })();

  // ======== Validasi ringan: kontrak >= TMT ========
  (function(){
    const tmt = document.getElementById('tmt');
    const end = document.getElementById('contract_end_date');
    const help = document.getElementById('contractHelp');
    function check(){
      if(!tmt.value || !end.value) { help.textContent=''; return; }
      const ok = new Date(end.value) >= new Date(tmt.value);
      help.textContent = ok ? '' : 'Tanggal akhir kontrak tidak boleh sebelum TMT.';
      help.style.color = ok ? '' : '#dc3545';
    }
    tmt?.addEventListener('change', check);
    end?.addEventListener('change', check);
    check();
  })();
</script>
@endpush
