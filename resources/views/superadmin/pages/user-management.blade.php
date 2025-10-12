{{-- resources/views/superadmin/pages/user-management.blade.php --}}
@extends('layouts.master')

@section('title','Manajemen Pengguna')

@section('content')
<div class="container-fluid">

  {{-- Flash --}}
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  {{-- ================= HERO CARD ================= --}}
  <div class="card hero-card shadow-sm mb-4">
    <div class="hero-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
      <div>
        <h2 class="hero-title mb-1">Manajemen Pengguna</h2>
        <div class="hero-meta">
          Total: <strong>{{ $users->total() }}</strong>
          <span class="d-none d-md-inline">•</span>
          <span class="d-block d-md-inline">Tampil: <strong>{{ $users->count() }}</strong> (halaman ini)</span>
        </div>
      </div>

      {{-- Actions (pill buttons) --}}
      <div class="hero-actions mt-3 mt-md-0">
        <a href="{{ route('superadmin.users.create') }}" class="btn btn-primary btn-pill btn-elev">
          <i class="fas fa-user-plus mr-1"></i> Tambah Pengguna
        </a>
      </div>
    </div>
  </div>

  {{-- ================= FILTER (mini-hero) ================= --}}
  <form method="GET" action="{{ request()->url() }}" class="card filter-card mb-3">
    <div class="card-body">
      <div class="filter-grid pill">
        <div>
          <label class="filter-label">Cari</label>
          <input type="text"
                 name="search"
                 id="searchBox"
                 class="form-control"
                 placeholder="Nama atau email…"
                 value="{{ request('search') }}">
        </div>

        <div>
          <label class="filter-label">Role</label>
          <select name="role" class="form-select">
            <option value="">— Semua Role —</option>
            @foreach($roles as $value => $label)
              <option value="{{ $value }}" @selected(request('role')===$value)>{{ $label }}</option>
            @endforeach
          </select>
        </div>

        <div class="ms-md-auto">
          <div class="toolbar">
            <button class="btn btn-primary btn-pill" type="submit">
              <i class="fas fa-search mr-1"></i> Terapkan 
            </button>
            <a href="{{ request()->url() }}" class="btn btn-outline-secondary btn-pill">
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
              <th style="width:64px">No</th>
              <th>Pengguna</th>
              <th>Email</th>
              <th>Role</th>
              <th class="text-center" style="width:160px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($users as $index => $user)
              @php
                $initial = strtoupper(mb_substr($user->name ?? 'U', 0, 1));
                $roleBadge = [
                  'superadmin' => 'badge-super',
                  'admin'      => 'badge-admin',
                  'employee'   => 'badge-emp',
                  'developer'  => 'badge-dev',
                ][$user->role] ?? 'badge-emp';
              @endphp
              <tr>
                <td>{{ $users->firstItem() + $index }}</td>
                <td>
                  <div class="d-flex align-items-center" style="gap:.6rem">
                    <div class="avatar-initial">{{ $initial }}</div>
                    <div>
                      <div class="fw-semibold">{{ $user->name }}</div>
                      <div class="small text-muted">ID: {{ $user->id }}</div>
                    </div>
                  </div>
                </td>
                <td class="text-muted">{{ $user->email }}</td>
                <td>
                  <span class="badge role-badge {{ $roleBadge }}">{{ ucfirst($user->role) }}</span>
                </td>
                <td class="text-center">
                  <div class="d-flex justify-content-center" style="gap:.35rem">
                    <a href="{{ route('superadmin.users.edit', $user) }}" class="btn btn-warning btn-sm btn-pill" title="Ubah">
                      <i class="fas fa-pencil-alt"></i>
                    </a>
                    <form action="{{ route('superadmin.users.destroy', $user) }}" method="POST" class="d-inline delete-form">
                      @csrf @method('DELETE')
                      <button type="button" class="btn btn-danger btn-sm btn-pill btn-delete" title="Hapus">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-4">Tidak ada data.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center">
      <div class="small text-muted">
        Menampilkan {{ $users->firstItem() }}–{{ $users->lastItem() }} dari {{ $users->total() }} pengguna
      </div>
      {{ $users->withQueryString()->links('pagination::bootstrap-4') }}
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
  @media (min-width:768px){ .hero-body{ padding: 1.35rem 1.5rem; } }
  .hero-title{ font-weight:700; letter-spacing:.2px; }
  .hero-meta{ opacity:.95; }
  .hero-actions{ display:flex; flex-wrap:wrap; gap:.5rem; }
  .btn-pill{ border-radius:999px!important; }
  .btn-elev{ box-shadow:0 10px 24px -12px rgba(13,110,253,.6); }

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
  @media (min-width:768px){ .filter-card .card-body{ padding:1rem 1.25rem; } }
  .filter-label{ font-size:.8rem; color:#6c757d; margin-bottom:.25rem }
  .pill .form-control, .pill .form-select{
    border-radius:999px; height:44px; padding:0 .9rem;
  }
  .toolbar{ display:flex; gap:.5rem; }

  /* Table */
  .table-sticky thead th{ position:sticky; top:0; z-index:2; background:#fff; }
  .table-nowrap th, .table-nowrap td{ white-space:nowrap; }
  .table td, .table th{ vertical-align:middle; }

  /* Avatar (initial) — selaras avatar foto di halaman Pegawai */
  .avatar-initial{
    width:44px; height:44px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    background: #eef2ff;
    color:#4f46e5; font-weight:700;
    border:2px solid #e9ecef;
  }

  /* Role badges */
  .role-badge{ border:1px solid transparent; font-weight:600; }
  .badge-super{ background:#6f42c1; color:#fff; }
  .badge-admin{ background:#0d6efd; color:#fff; }
  .badge-emp{ background:#20c997; color:#fff; }
  .badge-dev{ background:#495057; color:#fff; }

  .pagination .page-link{ padding:.25rem .5rem; font-size:.875rem; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Konfirmasi hapus
  document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function() {
      Swal.fire({
        title: 'Yakin ingin menghapus?',
        text: 'Data pengguna akan dihapus permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
      }).then(result => {
        if (result.isConfirmed) {
          this.closest('form.delete-form').submit();
        }
      });
    });
  });

  // Auto-submit pencarian (debounce) biar terasa responsif
  (function(){
    const box = document.getElementById('searchBox');
    if (!box) return;
    let t; box.addEventListener('input', () => {
      clearTimeout(t); t = setTimeout(() => box.form.submit(), 450);
    });
  })();
</script>
@endpush
