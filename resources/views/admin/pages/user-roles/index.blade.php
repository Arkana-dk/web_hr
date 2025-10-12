@extends('layouts.master')
@section('title','Kelola Role User')

@section('content')
<div class="container-fluid">

  {{-- Flash --}}
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  {{-- =============== HERO CARD =============== --}}
  <div class="card hero-card shadow-sm mb-4">
    <div class="hero-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
      <div>
        <h2 class="hero-title mb-1">Kelola Role User</h2>
        <div class="hero-meta">
          Total: <strong>{{ $users->total() }}</strong>
          <span class="d-none d-md-inline">•</span>
          <span class="d-block d-md-inline">Tampil: <strong>{{ $users->count() }}</strong> (halaman ini)</span>
          @if(($filter['role'] ?? '') !== '' || ($filter['q'] ?? '') !== '')
            <span class="d-none d-md-inline">•</span>
            <span class="d-block d-md-inline">
              Filter:
              @if(($filter['role'] ?? '') !== '')
                <span class="chip">Role: {{ $filter['role'] }}</span>
              @endif
              @if(($filter['q'] ?? '') !== '')
                <span class="chip">Kata kunci: “{{ $filter['q'] }}”</span>
              @endif
            </span>
          @endif
        </div>
      </div>
      <div class="hero-actions mt-3 mt-md-0">
      
      </div>
    </div>
  </div>

  {{-- =============== FILTER (mini-hero) =============== --}}
  <form method="GET" action="{{ request()->url() }}" class="card filter-card mb-3">
    <div class="card-body">
      <div class="filter-grid pill">
        <div>
          <label class="filter-label">Cari</label>
          <div class="input-icon">
            <i class="fas fa-search"></i>
            <input name="q"
                   id="searchBox"
                   value="{{ $filter['q'] ?? '' }}"
                   class="form-control"
                   placeholder="Cari nama atau email… (otomatis)">
          </div>
        </div>

        <div>
          <label class="filter-label">Role</label>
          <select name="role" id="roleSelect" class="form-select">
            <option value="">— Semua Role —</option>
            @foreach($allRoles as $r)
              <option value="{{ $r }}" {{ ($filter['role'] ?? '')===$r?'selected':'' }}>{{ $r }}</option>
            @endforeach
          </select>
        </div>

        <div class="ms-md-auto">
          <div class="toolbar">
            <button class="btn btn-primary btn-pill" type="submit">
              <i class="fas fa-filter mr-1"></i> Terapkan
            </button>
            <a href="{{ request()->url() }}" class="btn btn-outline-secondary btn-pill">Reset</a>
          </div>
        </div>
      </div>
    </div>
  </form>

  {{-- =============== TABLE =============== --}}
  <div class="card shadow rounded-4">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered table-hover table-sticky align-middle w-100 table-nowrap mb-0">
          <thead class="thead-light">
            <tr>
              <th>Pengguna</th>
              <th>Email</th>
              <th>Roles</th>
              <th class="text-center" style="width:120px">Aksi</th>
            </tr>
          </thead>
          <tbody>
          @forelse($users as $u)
            @php
              $initial = strtoupper(mb_substr($u->name ?? 'U', 0, 1));
              $badgeMap = [
                'super-admin' => 'danger',
                'admin'       => 'primary',
                'employee'    => 'success',
                'user'        => 'secondary',
              ];
            @endphp
            <tr class="row-link" data-href="{{ route('admin.user-roles.edit',$u) }}">
              <td>
                <div class="d-flex align-items-center" style="gap:.6rem">
                  <div class="avatar-initial">{{ $initial }}</div>
                  <div>
                    <div class="fw-semibold">{{ $u->name }}</div>
                    <div class="small text-muted">ID: {{ $u->id }}</div>
                  </div>
                </div>
              </td>
              <td class="text-monospace text-muted">{{ $u->email }}</td>
              <td>
                @forelse($u->roles as $role)
                  @php $cls = $badgeMap[$role->name] ?? 'info'; @endphp
                  <span class="badge badge-{{ $cls }} role-badge mr-1 mb-1">
                    <i class="fas fa-shield-alt mr-1"></i>{{ $role->name }}
                  </span>
                @empty
                  <span class="badge badge-light text-muted">—</span>
                @endforelse
              </td>
              <td class="text-center">
                <a href="{{ route('admin.user-roles.edit',$u) }}"
                   class="btn btn-sm btn-outline-primary btn-pill">
                  <i class="fas fa-edit"></i>
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center py-5 text-muted">
                <div class="mb-2"><i class="far fa-folder-open fa-2x"></i></div>
                <div class="mb-1 fw-semibold">Belum ada data</div>
                <div class="small">Coba ubah kata kunci atau reset filter.</div>
              </td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Pagination --}}
    @if($users->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="small text-muted">
          Menampilkan {{ $users->firstItem() }}–{{ $users->lastItem() }} dari {{ $users->total() }} data
        </div>
        <div class="ml-auto">
          {{ $users->withQueryString()->links('pagination::bootstrap-4') }}
        </div>
      </div>
    @endif
  </div>

</div>
@endsection

@push('styles')
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
  .chip{
    display:inline-flex; align-items:center; gap:.35rem;
    background:rgba(255,255,255,.15); color:#fff; border-radius:999px;
    padding:.15rem .6rem; font-size:.8rem;
  }

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
  .input-icon{ position:relative; }
  .input-icon>i{ position:absolute; left:.75rem; top:50%; transform:translateY(-50%); opacity:.6 }
  .input-icon>input{ padding-left:2.15rem; }

  /* Table */
  .table-sticky thead th{ position:sticky; top:0; z-index:2; background:#fff; }
  .table-nowrap th, .table-nowrap td{ white-space:nowrap; }
  .table td, .table th{ vertical-align:middle; }
  .row-link{ cursor:pointer; }
  .table-hover tbody tr:hover{ background:#fff8e1 !important; } /* kuning lembut */

  /* Avatar inisial (selaras halaman Pegawai) */
  .avatar-initial{
    width:36px; height:36px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    background:#eef2ff; color:#4f46e5; font-weight:700; border:2px solid #e9ecef;
  }

  /* Role badges */
  .role-badge{ text-transform:uppercase; letter-spacing:.02em; font-weight:600; }
  .pagination .page-link{ padding:.25rem .5rem; font-size:.875rem; }
</style>
@endpush

@push('scripts')
<script>
  // Klik baris -> ke halaman edit
  document.querySelectorAll('.row-link').forEach(tr => {
    tr.addEventListener('click', function(e){
      // biar klik tombol edit tidak trigger row-link
      if(e.target.closest('a, button')) return;
      const href = this.dataset.href;
      if(href) window.location = href;
    });
  });

  // Auto-submit saat ganti role
  document.getElementById('roleSelect')?.addEventListener('change', function(){ this.form.submit(); });

  // Auto-submit pencarian (debounce)
  (function(){
    const box = document.getElementById('searchBox');
    if(!box) return;
    let t;
    box.addEventListener('input', () => {
      clearTimeout(t);
      t = setTimeout(() => box.form.submit(), 450);
    });
  })();
</script>
@endpush
