@extends('layouts.master')

@section('title','Detail Pegawai')

@push('styles')
<style>
  :root{ --hero-grad: linear-gradient(135deg,#0d6efd 0%, #6f42c1 100%); }
  .hero-card{ background:var(--hero-grad); color:#fff; border:0; border-radius:1.25rem; overflow:hidden; box-shadow:0 12px 28px -18px rgba(13,110,253,.55); }
  .hero-body{ padding:1.2rem 1.35rem; } @media(min-width:768px){ .hero-body{ padding:1.6rem 1.75rem; } }
  .btn-pill{ border-radius:999px!important; } .btn-soft-white{ background:rgba(255,255,255,.15); border-color:rgba(255,255,255,.25); color:#fff; }
  .btn-soft-white:hover{ background:rgba(255,255,255,.25); color:#fff; } .btn-elev{ box-shadow:0 10px 24px -12px rgba(0,0,0,.35); }
  .avatar-lg{ width:110px; height:110px; border-radius:50%; overflow:hidden; border:2px solid rgba(255,255,255,.6); } .avatar-lg img{ width:100%; height:100%; object-fit:cover; }
  .hero-title{ font-weight:700; letter-spacing:.2px; margin-bottom:.25rem; } .hero-meta{ opacity:.95; font-size:.95rem; }
  .chip{ display:inline-flex; align-items:center; gap:.4rem; padding:.35rem .6rem; border-radius:999px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.28); font-weight:600; font-size:.8rem; color:#fff; }

  .stat-card{ border:1px solid rgba(0,0,0,.06); border-radius:1rem; } .stat-body{ padding:.9rem 1rem; display:flex; justify-content:space-between; align-items:center; }
  .stat-label{ font-size:.8rem; color:#6c757d; margin-bottom:.25rem } .stat-value{ font-weight:700; font-variant-numeric:tabular-nums; }

  .card.soft{ border:0; border-radius:1rem;
    background:linear-gradient(#fff,#fff) padding-box, linear-gradient(135deg, rgba(13,110,253,.18), rgba(111,66,193,.18)) border-box;
    border:1px solid transparent; box-shadow:0 12px 30px -20px rgba(13,110,253,.35); }
  .card.soft .card-header{ background:#fff; border-bottom:1px solid rgba(0,0,0,.06); border-top-left-radius:1rem; border-top-right-radius:1rem; font-weight:600; }

  .dl-grid dt{ color:#6c757d; width:38%; float:left; clear:left; padding:.4rem .2rem; } .dl-grid dd{ width:62%; float:left; padding:.4rem .2rem; margin:0; }
  @media (max-width:576px){ .dl-grid dt,.dl-grid dd{ width:100%; padding:.25rem 0; } }
  .copyable{ display:inline-flex; align-items:center; gap:.35rem; } .btn-icon-sm{ border-radius:999px; padding:.15rem .45rem; line-height:1; }
  .badge-soft{ background:rgba(13,110,253,.08); color:#0d6efd; border:1px solid rgba(13,110,253,.18); }
</style>
@endpush

@section('content')
<div class="container-fluid">

  @php
    use Carbon\Carbon;
    // Tanggal penting
    $dob  = $employee->date_of_birth ? Carbon::parse($employee->date_of_birth) : null;
    $tmt  = $employee->tmt ? Carbon::parse($employee->tmt) : null;
    $cEnd = $employee->contract_end_date ? Carbon::parse($employee->contract_end_date) : null;

    // Ringkasan
    $ageY  = $dob ? $dob->age : null;
    $ten   = $tmt ? $tmt->diff(Carbon::now()) : null;
    $leftD = $cEnd ? Carbon::now()->diffInDays($cEnd, false) : null;

    // Fallback untuk field yang beda nama
    $NIK = $employee->national_identity_number ?? $employee->nik ?? null;
    $KK  = $employee->family_number_card ?? $employee->kk_number ?? null;
  @endphp

  {{-- ================= HERO ================= --}}
  <div class="card hero-card mb-4">
    <div class="hero-body d-flex flex-column gap-3 gap-md-0 flex-md-row align-items-start align-items-md-center justify-content-between">
      <div class="d-flex align-items-center gap-3">
        <div class="avatar-lg">
          @if($employee->photo)
            <img src="{{ asset('storage/'.$employee->photo) }}" alt="Foto {{ $employee->name }}">
          @else
            <img src="{{ asset('images/default-user.png') }}" alt="Foto default">
          @endif
        </div>
        <div>
          <div class="hero-title h3 mb-0">{{ $employee->name }}</div>
          <div class="hero-meta">
            <span class="chip"><i class="fas fa-id-badge"></i> {{ $employee->employee_number ?? '—' }}</span>
            @if($employee->position?->name)<span class="chip"><i class="fas fa-briefcase"></i> {{ $employee->position->name }}</span>@endif
            @if($employee->department?->name)<span class="chip"><i class="fas fa-building"></i> {{ $employee->department->name }}</span>@endif
            @if($employee->payGroup?->name)<span class="chip"><i class="fas fa-coins"></i> {{ $employee->payGroup->name }}</span>@endif
          </div>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.employees.index') }}" class="btn btn-soft-white btn-pill btn-elev"><i class="fas fa-arrow-left"></i> Kembali</a>
        <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-light btn-pill btn-elev"><i class="fas fa-pencil-alt"></i> Edit</a>
        <form action="{{ route('admin.employees.destroy', $employee) }}" method="POST" class="d-inline delete-form">
          @csrf @method('DELETE')
          <button type="button" class="btn btn-danger btn-pill btn-elev btn-delete"><i class="fas fa-trash"></i> Hapus</button>
        </form>
      </div>
    </div>
  </div>

  {{-- ================= SUMMARY STRIP ================= --}}
  <div class="row g-3 mb-4">
    <div class="col-12 col-md-4"><div class="card stat-card h-100"><div class="stat-body"><div><div class="stat-label">Usia</div><div class="stat-value fs-5">{{ $ageY !== null ? $ageY.' th' : '—' }}</div></div><i class="fas fa-birthday-cake text-secondary fs-4"></i></div></div></div>
    <div class="col-12 col-md-4"><div class="card stat-card h-100"><div class="stat-body"><div><div class="stat-label">Masa Kerja</div><div class="stat-value fs-5">@if($ten) {{ $ten->y }} th {{ $ten->m }} bln @else — @endif</div></div><i class="fas fa-user-clock text-secondary fs-4"></i></div></div></div>
    <div class="col-12 col-md-4"><div class="card stat-card h-100"><div class="stat-body"><div><div class="stat-label">Sisa Kontrak</div><div class="stat-value fs-5">@if($leftD === null) — @elseif($leftD >= 0) {{ $leftD }} hari @else <span class="text-danger">Habis {{ abs($leftD) }} hari lalu</span> @endif</div></div><i class="fas fa-calendar-check text-secondary fs-4"></i></div></div></div>
  </div>

  {{-- ================= DETAIL SECTIONS ================= --}}
  <div class="row g-3">

    {{-- KIRI --}}
    <div class="col-lg-6">
      <div class="card soft mb-3">
        <div class="card-header">Informasi Kepegawaian</div>
        <div class="card-body">
          <dl class="dl-grid clearfix mb-0">
            <dt>Nomor Karyawan</dt>
            <dd class="copyable"><span id="val-empnum">{{ $employee->employee_number ?? '—' }}</span>
              @if($employee->employee_number)<button class="btn btn-light btn-sm btn-icon-sm" data-copy="#val-empnum"><i class="fas fa-copy"></i></button>@endif
            </dd>

            <dt>Role Akses</dt>
            <dd>@if($employee->role)<span class="badge badge-soft text-uppercase">{{ $employee->role }}</span>@else — @endif</dd>

            <dt>Department</dt><dd>{{ $employee->department->name ?? '—' }}</dd>
            <dt>Seksi</dt><dd>{{ $employee->section->name ?? '—' }}</dd>
            <dt>Grup</dt><dd>{{ $employee->group->name ?? '—' }}</dd>
            <dt>Posisi</dt><dd>{{ $employee->position->name ?? '—' }}</dd>
            <dt>Pay Group (Payroll)</dt><dd>{{ $employee->payGroup->name ?? '—' }}</dd>
            <dt>Title</dt><dd>{{ $employee->title ?? '—' }}</dd>
            <dt>TMT (Mulai Bekerja)</dt><dd>{{ $employee->tmt ? Carbon::parse($employee->tmt)->format('d M Y') : '—' }}</dd>
            <dt>Akhir Kontrak</dt><dd>{{ $employee->contract_end_date ? Carbon::parse($employee->contract_end_date)->format('d M Y') : '—' }}</dd>

            <dt>Dibuat / Diperbarui</dt>
            <dd>
              {{ $employee->created_at ? $employee->created_at->format('d M Y H:i') : '—' }}
              <span class="text-muted">•</span>
              {{ $employee->updated_at ? $employee->updated_at->format('d M Y H:i') : '—' }}
            </dd>
          </dl>
        </div>
      </div>

      <div class="card soft mb-3">
        <div class="card-header">Identitas Pribadi</div>
        <div class="card-body">
          <dl class="dl-grid clearfix mb-0">
            <dt>NIK</dt>
            <dd class="copyable"><span id="val-nik">{{ $NIK ?? '—' }}</span>
              @if($NIK)<button class="btn btn-light btn-sm btn-icon-sm" data-copy="#val-nik"><i class="fas fa-copy"></i></button>@endif
            </dd>

            <dt>No. KK</dt>
            <dd class="copyable"><span id="val-kk">{{ $KK ?? '—' }}</span>
              @if($KK)<button class="btn btn-light btn-sm btn-icon-sm" data-copy="#val-kk"><i class="fas fa-copy"></i></button>@endif
            </dd>

            <dt>Tempat / Tgl Lahir</dt>
            <dd>{{ $employee->place_of_birth ?? '—' }}, {{ $employee->date_of_birth ? Carbon::parse($employee->date_of_birth)->format('d M Y') : '—' }}</dd>

            <dt>Jenis Kelamin</dt>
            <dd>@if($employee->gender)<span class="badge badge-soft">{{ $employee->gender }}</span>@else — @endif</dd>

            <dt>Agama</dt><dd>{{ $employee->religion ?? '—' }}</dd>
            <dt>Status Kawin</dt><dd>{{ $employee->marital_status ?? '—' }}</dd>
            <dt>Pendidikan</dt><dd>{{ $employee->education ?? '—' }}</dd>
            <dt>Tanggungan</dt><dd>{{ $employee->dependents_count ?? 0 }} orang</dd>
          </dl>
        </div>
      </div>
    </div>

    {{-- KANAN --}}
    <div class="col-lg-6">
      <div class="card soft mb-3">
        <div class="card-header">Kontak & Alamat</div>
        <div class="card-body">
          <dl class="dl-grid clearfix mb-0">
            <dt>Email</dt>
            <dd class="copyable"><span id="val-email">{{ $employee->email ?? '—' }}</span>
              @if($employee->email)<button class="btn btn-light btn-sm btn-icon-sm" data-copy="#val-email"><i class="fas fa-copy"></i></button>@endif
            </dd>

            <dt>Telepon</dt>
            <dd class="copyable"><span id="val-phone">{{ $employee->phone ?? '—' }}</span>
              @if($employee->phone)<button class="btn btn-light btn-sm btn-icon-sm" data-copy="#val-phone"><i class="fas fa-copy"></i></button>@endif
            </dd>

            <dt>Alamat</dt><dd>{{ $employee->address ?? '—' }}</dd>
          </dl>
        </div>
      </div>

      <div class="card soft mb-3">
        <div class="card-header">Payroll & Bank</div>
        <div class="card-body">
          <dl class="dl-grid clearfix mb-0">
            <dt>Gaji Pokok</dt><dd>{{ $employee->salary ? 'Rp '.number_format($employee->salary, 0, ',', '.') : '—' }}</dd>

            <dt>Nama Bank</dt><dd>{{ $employee->bank_name ?? '—' }}</dd>

            <dt>Rekening Bank</dt>
            <dd class="copyable">
              <span id="val-bank">
                {{ $employee->bank_account_name ?? '—' }} / {{ $employee->bank_account_number ?? '—' }}
              </span>
              @if($employee->bank_account_number)
                <button class="btn btn-light btn-sm btn-icon-sm" data-copy="#val-bank"><i class="fas fa-copy"></i></button>
              @endif
            </dd>
          </dl>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Copy to clipboard
  document.querySelectorAll('[data-copy]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.querySelector(btn.getAttribute('data-copy'));
      if(!target) return;
      const text = (target.textContent || '').trim();
      navigator.clipboard.writeText(text).then(() => {
        Swal.fire({toast:true, position:'top-end', timer:1500, showConfirmButton:false, icon:'success', title:'Disalin'});
      });
    });
  });

  // Confirm delete
  document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function () {
      Swal.fire({
        title:'Yakin ingin menghapus?', text:'Data pegawai akan dihapus permanen.',
        icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus', cancelButtonText:'Batal'
      }).then(res => { if(res.isConfirmed) this.closest('form.delete-form').submit(); });
    });
  });
</script>
@endpush
