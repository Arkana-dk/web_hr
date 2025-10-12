@extends('layouts.master')
@section('title','Ubah Role User')

@push('styles')
<style>
  .page-header { gap:.5rem }
  .role-card { border: 1px solid rgba(0,0,0,.06); border-radius: 14px; transition: box-shadow .15s ease, transform .05s ease; }
  .role-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.06); }
  .role-chip { text-transform: uppercase; letter-spacing:.02em; }
  .switch-wrap { display:flex; align-items:center; gap:.6rem; }
  .help-text { color:#6c757d }
  .sticky-actions { position: sticky; bottom: 0; background: #fff; border-top: 1px solid rgba(0,0,0,.06); padding: .75rem; z-index: 10; }
  .danger-note { border-left: 4px solid #e74a3b; }
  .success-note { border-left: 4px solid #1cc88a; }
  .kbd { background: #f1f3f5; border:1px solid #dee2e6; border-bottom-width:2px; padding:.1rem .35rem; border-radius:.25rem; font-size: .85em; }
  .btn-oval { border-radius: 50rem !important; padding-left: 1.25rem; padding-right: 1.25rem; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

  {{-- Header --}}
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center page-header mb-3">
    <div>
      <h1 class="h4 mb-1 d-flex align-items-center">
        <i class="fas fa-user-shield mr-2"></i> Ubah Role: {{ $user->name }}
      </h1>
      <div class="small text-muted">
        Email: <span class="text-monospace">{{ $user->email }}</span>
      </div>
    </div>

    {{-- Ringkas status --}}
    <div class="text-muted small">
      Role aktif:
      @forelse($user->roles as $r)
        <span class="badge badge-info role-chip mr-1">{{ $r->name }}</span>
      @empty
        <span class="badge badge-light">—</span>
      @endforelse
    </div>
  </div>

  @php
    /** @var \App\Models\User $actor */
    $actor = auth()->user();
    // Role yang disembunyikan dari HR-Admin
    $hiddenForHrAdmin = ['super-admin','payroll','payroll-admin'];
    $shouldHide = $actor && $actor->hasRole('hr-admin');

    // Build daftar assignable efektif untuk UI
    $effectiveAssignable = $assignable;
    if ($shouldHide) {
      foreach ($hiddenForHrAdmin as $blocked) {
        unset($effectiveAssignable[$blocked]);
      }
    }

    // Helper deskripsi & warna
    $descs = [
      'super-admin' => 'Akses penuh seluruh modul & pengaturan.',
      'admin'       => 'Kelola data master & approval inti.',
      'hr-staff'    => 'Kelola attendance, jadwal, & data karyawan.',
      'employee'    => 'Akses self-service (profil, absensi, request).',
      'user'        => 'Akses paling dasar.',
      'payroll'     => 'Kelola pay run, komponen gaji, dan laporan.',
      'payroll-admin'=> 'Akses administrasi modul payroll.',
    ];
    $accents = [
      'super-admin'   => 'danger',
      'admin'         => 'primary',
      'hr-staff'      => 'warning',
      'employee'      => 'success',
      'user'          => 'secondary',
      'payroll'       => 'info',
      'payroll-admin' => 'info',
    ];
  @endphp

  {{-- Kebijakan/peringatan --}}
  <div class="alert alert-warning danger-note mb-3">
    <div class="d-flex">
      <i class="fas fa-exclamation-triangle mr-2 mt-1"></i>
      <div>
        <div class="font-weight-bold mb-1">Kebijakan Role</div>
        <div>
          HR-Admin hanya boleh mengatur: <strong>hr-staff</strong> dan <strong>employee</strong>.
          Menghapus <em>super-admin</em> terakhir dilarang.
          @if($shouldHide)
            <div class="mt-1 small text-muted">
              Beberapa opsi disembunyikan: <em>super-admin</em>, <em>payroll</em> (dan <em>payroll-admin</em>).
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Form --}}
  <form method="POST" action="{{ route('admin.user-roles.update', $user) }}" id="roleForm">
    @csrf @method('PUT')

    {{-- Grid pilihan role (hanya yang diizinkan tampil) --}}
    <div class="row">
      @forelse($effectiveAssignable as $name => $label)
        @php
          $checked = $user->hasRole($name);
          $color = $accents[$name] ?? 'info';
          $desc = $descs[$name] ?? '—';
        @endphp
        <div class="col-md-6 col-lg-4 mb-3">
          <div class="role-card h-100 p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div class="switch-wrap">
                <div class="custom-control custom-switch">
                  <input type="checkbox"
                         class="custom-control-input role-switch"
                         id="role_{{ $name }}"
                         name="roles[]" value="{{ $name }}"
                         {{ $checked ? 'checked' : '' }}>
                  <label class="custom-control-label" for="role_{{ $name }}"></label>
                </div>
                <div class="ml-1">
                  <div class="font-weight-600">
                    <i class="fas fa-shield-alt text-{{ $color }} mr-1"></i>{{ $label }}
                  </div>
                  <div class="small help-text">{{ $desc }}</div>
                </div>
              </div>
              @if($checked)
                <span class="badge badge-{{ $color }} role-chip">aktif</span>
              @endif
            </div>
            <div class="d-flex align-items-center help-text small">
              <i class="far fa-lightbulb mr-1"></i>
              Tekan <span class="kbd ml-1 mr-1">Space</span> saat fokus untuk toggle.
            </div>
          </div>
        </div>
      @empty
        <div class="col-12">
          <div class="alert alert-light border">Tidak ada role yang bisa diubah.</div>
        </div>
      @endforelse
    </div>

    {{-- Tips --}}
    <div class="alert alert-light success-note">
      <div class="d-flex">
        <i class="fas fa-info-circle mr-2 mt-1"></i>
        <div>
          Perubahan role berlaku setelah <em>submit</em>. Pastikan setidaknya satu role tetap aktif agar akun tidak kehilangan akses.
        </div>
      </div>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
  @csrf
  @method('PUT')

  <div class="card mb-3">
    <div class="card-header">
      <h6 class="mb-0"><i class="fas fa-user-edit mr-2"></i> Edit Informasi User</h6>
    </div>
    <div class="card-body">
      
      {{-- Email --}}
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" class="form-control"
               value="{{ old('email', $user->email) }}" required>
        @error('email')
          <small class="text-danger">{{ $message }}</small>
        @enderror
      </div>

    {{-- Password Baru --}}
      <div class="form-group position-relative">
        <label for="password">Password Baru</label>
        <div class="input-group">
          <input type="password" name="password" id="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah">
          <div class="input-group-append">
            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#password">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
        @error('password')
          <small class="text-danger">{{ $message }}</small>
        @enderror
      </div>

      {{-- Konfirmasi Password --}}
      <div class="form-group position-relative">
        <label for="password_confirmation">Konfirmasi Password Baru</label>
        <div class="input-group">
          <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Ulangi password baru">
          <div class="input-group-append">
            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#password_confirmation">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
      </div>


    {{-- Sticky actions --}}
    <div class="sticky-actions d-flex flex-column flex-md-row align-items-md-center">
      <div class="text-muted small mr-md-auto mb-2 mb-md-0">
        <span id="selectedCount">0</span> role dipilih.
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-oval">
          <i class="fas fa-save mr-1"></i> Simpan
        </button>
        <a href="{{ route('admin.user-roles.index') }}" class="btn btn-light btn-oval">
          <i class="fas fa-arrow-left mr-1"></i> Batal
        </a>
      </div>
    </div>
  </form>
</div>
@endsection



@push('scripts')
<script>
document.querySelectorAll('.toggle-password').forEach(btn => {
  btn.addEventListener('click', function () {
    const target = document.querySelector(this.dataset.target);
    const icon = this.querySelector('i');
    if (target.type === 'password') {
      target.type = 'text';
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
    } else {
      target.type = 'password';
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
    }
  });
});
</script>

<script>
(function(){
  const form = document.getElementById('roleForm');
  const switches = Array.from(document.querySelectorAll('.role-switch'));
  const selectedCountEl = document.getElementById('selectedCount');

  function updateCount(){
    const count = switches.filter(s => s.checked).length;
    selectedCountEl.textContent = count;
  }

  // Minimal 1 role
  form.addEventListener('submit', function(e){
    const count = switches.filter(s => s.checked).length;
    if(count === 0){
      e.preventDefault();
      (window.Swal ? Swal.fire({
        icon: 'warning',
        title: 'Tidak ada role dipilih',
        text: 'Pilih minimal satu role agar pengguna tetap memiliki akses.',
      }) : alert('Pilih minimal satu role.'));
    }
  });

  // (Opsional) Lindungi super-admin terakhir, jika server set flag
  @if(isset($isLastSuperAdmin) && $isLastSuperAdmin)
    const superSwitch = document.getElementById('role_super-admin');
    if (superSwitch) {
      superSwitch.addEventListener('change', function(e){
        if(!e.target.checked){
          e.preventDefault();
          e.target.checked = true;
          (window.Swal ? Swal.fire({
            icon: 'error',
            title: 'Aksi ditolak',
            text: 'Menghapus super-admin terakhir tidak diizinkan.'
          }) : alert('Menghapus super-admin terakhir tidak diizinkan.'));
        }
      });
    }
  @endif

  switches.forEach(s => s.addEventListener('change', updateCount));
  updateCount();
})();
</script>
@endpush
