{{-- resources/views/admin/transportroutes/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Daftar Jurusan Transportasi')

@push('styles')
<style>
  /* ===== Hero Card ===== */
  .hero-card {
    background: radial-gradient(1200px 400px at 10% -10%, rgba(59,130,246,.25), transparent 60%),
                radial-gradient(1200px 400px at 90% -10%, rgba(236,72,153,.25), transparent 60%),
                linear-gradient(135deg, #ffffff, #f8fafc);
    border: 1px solid rgba(0,0,0,.05);
    backdrop-filter: blur(4px);
  }
  .hero-card .shine {
    position: absolute; inset: 0;
    background: radial-gradient(400px 120px at 80% 10%, rgba(255,255,255,.5), transparent 60%);
    pointer-events: none;
  }
  .hero-emoji {
    font-size: 56px; line-height: 1;
    filter: drop-shadow(0 10px 18px rgba(0,0,0,.1));
    transform: translateY(2px);
  }

  /* ===== Table polish ===== */
  .table thead th { position: sticky; top: 0; z-index: 1; background: #f8fafc; }
  .table-hover tbody tr:hover { background: #f9fbff; }
  .table td, .table th { vertical-align: middle; }
  .chip {
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.35rem .6rem; border-radius:999px; font-weight:600; font-size:.78rem;
    background:#eef2ff; color:#3730a3;
  }

  /* ===== Buttons ===== */
  .btn-icon {
    display:inline-flex; align-items:center; gap:.4rem;
  }

  /* ===== Empty state ===== */
  .empty-illustration {
    font-size: 64px; line-height:1; opacity:.7;
  }
</style>
@endpush

@section('content')
<div class="container-fluid">

  {{-- ===== Hero Card ===== --}}
  @php
    $q = request('q');
    $totalRoutes = method_exists($routes, 'total') ? $routes->total() : $routes->count();
  @endphp

  <div class="card hero-card shadow-sm mb-4 position-relative overflow-hidden">
    <div class="shine"></div>
    <div class="card-body py-4 px-4 px-lg-5">
      <div class="d-flex flex-column flex-lg-row align-items-lg-center">
        <div class="flex-grow-1">
          <div class="d-flex align-items-center mb-2">
            <span class="hero-emoji mr-3">🚌</span>
            <div>
              <h1 class="h4 mb-1 text-gray-800">Daftar Jurusan Transportasi</h1>
              <p class="mb-0 text-muted">
                Kelola jurusan (rute) antar lokasi. Tambahkan, ubah, dan hapus rute dengan cepat.
              </p>
            </div>
          </div>

          <div class="d-flex flex-wrap align-items-center mt-3" style="gap:.5rem 1rem">
            <span class="chip"><i class="fas fa-route"></i> Total: {{ number_format($totalRoutes) }}</span>
            @if($q)
              <span class="chip" style="background:#ecfeff;color:#155e75;">
                <i class="fas fa-filter"></i> Filter: “{{ $q }}”
              </span>
            @endif
          </div>
        </div>

        <div class="mt-3 mt-lg-0 ml-lg-4">
          <a href="{{ route('admin.transportroutes.create') }}" class="btn btn-primary btn-icon shadow-sm">
            <i class="fas fa-plus"></i><span>Tambah Jurusan</span>
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- ===== Flash ===== --}}
  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
      <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
  @endif

  {{-- ===== Toolbar: Search + Actions ===== --}}
  <div class="card border-0 mb-3">
    <div class="card-body py-3 px-3 px-lg-4">
      <form method="GET" action="{{ route('admin.transportroutes.index') }}">
        <div class="form-row align-items-center">
          <div class="col-sm-6 col-md-5 mb-2 mb-md-0">
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
              </div>
              <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Cari nama jurusan…">
              @if($q)
                <div class="input-group-append">
                  <a href="{{ route('admin.transportroutes.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
              @endif
            </div>
            <small class="text-muted d-block mt-1">Gunakan kata kunci sebagian atau penuh.</small>
          </div>
          <div class="col-sm-6 col-md-7 text-sm-right">
            <a href="{{ route('admin.transportroutes.create') }}" class="btn btn-primary btn-icon">
              <i class="fas fa-plus"></i><span>Tambah Jurusan</span>
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- ===== Data Table ===== --}}
  <div class="card shadow-sm">
    <div class="card-header py-3 bg-white">
      <div class="d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-gray-800">Data Jurusan</h6>
        <span class="text-muted small">Menampilkan {{ method_exists($routes,'count') ? $routes->count() : count($routes) }} item</span>
      </div>
    </div>

    @if(($routes ?? collect())->isNotEmpty())
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped mb-0">
            <thead class="thead-light text-center">
              <tr>
                <th style="width:72px;">#</th>
                <th class="text-left">Nama Jurusan</th>
                <th style="width: 220px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($routes as $route)
                <tr>
                  <td class="text-center">{{ ($routes->firstItem() ?? 1) + $loop->index }}</td>
                  <td class="text-left">
                    <strong>{{ $route->route_name }}</strong>
                  </td>
                  <td class="text-center">
                    <div class="btn-group" role="group" aria-label="Actions">
                      <a href="{{ route('admin.transportroutes.edit', $route->id) }}"
                         class="btn btn-sm btn-warning btn-icon" data-toggle="tooltip" title="Edit">
                        <i class="fas fa-edit"></i><span class="d-none d-md-inline"> Edit</span>
                      </a>
                      <form action="{{ route('admin.transportroutes.destroy', $route->id) }}"
                            method="POST" class="d-inline"
                            onsubmit="return confirm('Yakin ingin menghapus jurusan ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger btn-icon" data-toggle="tooltip" title="Hapus">
                          <i class="fas fa-trash"></i><span class="d-none d-md-inline"> Hapus</span>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        {{-- Pagination --}}
        @if(method_exists($routes,'links'))
          <div class="px-3 px-lg-4 py-3">
            {{ $routes->appends(['q' => $q])->links() }}
          </div>
        @endif
      </div>
    @else
      {{-- Empty State --}}
      <div class="card-body text-center py-5">
        <div class="empty-illustration mb-3">🗺️</div>
        <h5 class="mb-2">Belum ada jurusan</h5>
        <p class="text-muted mb-4">Tambahkan jurusan transportasi pertama Anda untuk memulai.</p>
        <a href="{{ route('admin.transportroutes.create') }}" class="btn btn-primary btn-icon">
          <i class="fas fa-plus"></i><span>Tambah Jurusan</span>
        </a>
      </div>
    @endif
  </div>

</div>
@endsection

@push('scripts')
<script>
  // Tooltip init (opsional)
  $(function () { $('[data-toggle="tooltip"]').tooltip(); });

  // Auto-dismiss flash
  setTimeout(function(){
    $('.alert.alert-success').alert('close');
  }, 3500);
</script>
@endpush
