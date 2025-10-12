{{-- resources/views/admin/departments/index.blade.php --}}
@extends('layouts.master')

@section('title','Data Departments')

@push('styles')
<style>
  /* ===== Hero Card (sama seperti halaman Transport) ===== */
  .hero-card {
    background: radial-gradient(1200px 400px at 10% -10%, rgba(59,130,246,.25), transparent 60%),
                radial-gradient(1200px 400px at 90% -10%, rgba(236,72,153,.25), transparent 60%),
                linear-gradient(135deg, #ffffff, #f8fafc);
    border: 1px solid rgba(0,0,0,.05);
    backdrop-filter: blur(4px);
    border-radius: .75rem;
  }
  .hero-card .shine { position:absolute; inset:0; background: radial-gradient(400px 120px at 80% 10%, rgba(255,255,255,.5), transparent 60%); pointer-events:none; }
  .hero-emoji { font-size:56px; line-height:1; filter:drop-shadow(0 10px 18px rgba(0,0,0,.1)); transform: translateY(2px); }

  /* ===== Chips / Badges ===== */
  .chip {
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.35rem .6rem; border-radius:999px; font-weight:600; font-size:.78rem;
    background:#eef2ff; color:#3730a3;
  }

  /* ===== Table polish ===== */
  .table thead th { position: sticky; top: 0; z-index: 1; background: #f8fafc; }
  .table-hover tbody tr:hover { background: #f9fbff; }
  .table td, .table th { vertical-align: middle; }

  /* ===== Buttons ===== */
  .btn-icon { display:inline-flex; align-items:center; gap:.4rem; }

  /* ===== Empty state ===== */
  .empty-illustration { font-size: 64px; line-height:1; opacity:.7; }
</style>
@endpush

@section('content')
<div class="container-fluid">

  {{-- Flash (toast-friendly juga di script) --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
      <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
    </div>
  @endif

  {{-- ===== Hero Card (selaras dengan Transport) ===== --}}
  @php
    $q = request('q');
    $totalDepartments = method_exists($departments, 'total') ? $departments->total() : $departments->count();
  @endphp

  <div class="card hero-card shadow-sm mb-4 position-relative overflow-hidden">
    <div class="shine"></div>
    <div class="card-body py-4 px-4 px-lg-5">
      <div class="d-flex flex-column flex-lg-row align-items-lg-center">
        <div class="flex-grow-1">
          <div class="d-flex align-items-center mb-2">
            <span class="hero-emoji mr-3">🏢</span>
            <div>
              <h1 class="h4 mb-1 text-gray-800">Data Departments</h1>
              <p class="mb-0 text-muted">Kelola departemen Anda. Tambah, edit, dan hapus dengan cepat.</p>
            </div>
          </div>

          <div class="d-flex flex-wrap align-items-center mt-3" style="gap:.5rem 1rem">
            <span class="chip"><i class="fas fa-layer-group"></i> Total: {{ number_format($totalDepartments) }}</span>
            @if($q)
              <span class="chip" style="background:#ecfeff;color:#155e75;">
                <i class="fas fa-filter"></i> Filter: “{{ $q }}”
              </span>
            @endif
          </div>
        </div>

        <div class="mt-3 mt-lg-0 ml-lg-4">
          <button class="btn btn-primary btn-icon shadow-sm" data-toggle="collapse" data-target="#addDept" aria-expanded="false" aria-controls="addDept">
            <i class="fas fa-plus"></i><span> Tambah Department</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- ===== Toolbar: Search + Actions (meniru halaman Transport) ===== --}}
  <div class="card border-0 mb-3">
    <div class="card-body py-3 px-3 px-lg-4">
      <form method="GET" action="{{ route('admin.departments.index') }}">
        <div class="form-row align-items-center">
          <div class="col-sm-6 col-md-5 mb-2 mb-md-0">
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
              </div>
              <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Cari nama department…">
              @if($q)
                <div class="input-group-append">
                  <a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
              @endif
            </div>
            <small class="text-muted d-block mt-1">Gunakan kata kunci sebagian atau penuh.</small>
          </div>
          <div class="col-sm-6 col-md-7 text-sm-right">
            <button class="btn btn-outline-primary btn-icon" type="button" data-toggle="collapse" data-target="#addDept" aria-expanded="false" aria-controls="addDept">
              <i class="fas fa-plus"></i><span> Tambah Department</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- ===== Add Department (collapse form) ===== --}}
  <div class="collapse mb-4" id="addDept">
    <div class="card shadow-sm">
      <div class="card-body">
        <form action="{{ route('admin.departments.store') }}" method="POST" class="w-100">
          @csrf
          <div class="form-row">
            <div class="col-12 col-md-8 mb-2 mb-md-0">
              <input type="text" name="name" class="form-control" placeholder="Department Name" required>
            </div>
            <div class="col-12 col-md-4 d-flex">
              <button type="submit" class="btn btn-success rounded-pill px-4 ml-md-2 w-100 w-md-auto">Save</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- ===== Data Table (Desktop/Tablet) ===== --}}
  <div class="card shadow-sm">
    <div class="card-header py-3 bg-white">
      <div class="d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-gray-800">Daftar Department</h6>
        <span class="text-muted small">Menampilkan {{ method_exists($departments,'count') ? $departments->count() : count($departments) }} item</span>
      </div>
    </div>

    <div class="card-body">
      <div class="table-responsive-md d-none d-md-block">
        @php
  $rowStart = method_exists($departments, 'firstItem') ? $departments->firstItem() : 1;
      @endphp

        <table id="departments-table" class="table table-bordered table-hover table-striped align-middle" width="100%">
          <thead class="thead-light text-center">
            <tr>
              <th style="width:5%">#</th>
              <th class="text-left">Name</th>
              <th style="width:15%">Sections</th>
              <th style="width:35%">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($departments as $dept)
              @php($sectionCount = $dept->sections_count ?? $dept->sections->count())
              <tr>
                <td class="text-center">{{ $rowStart + $loop->index }}</td>
                <td class="text-left text-truncate" style="max-width: 320px">{{ $dept->name }}</td>
                <td class="text-center">
                  <span class="badge badge-info badge-pill">{{ $sectionCount }} Section</span>
                </td>
                <td>
                  <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="{{ route('admin.sections.index', ['filter_department' => $dept->id]) }}" class="btn btn-info btn-sm rounded-pill px-3 mb-2 mr-2 btn-icon">
                      <i class="fas fa-list"></i><span class="d-none d-lg-inline"> Show Sections</span>
                    </a>
                    <a href="{{ route('admin.departments.edit', $dept) }}" class="btn btn-warning btn-sm rounded-pill px-3 mb-2 mr-2 btn-icon">
                      <i class="fas fa-edit"></i><span class="d-none d-lg-inline"> Edit</span>
                    </a>
                    @if($sectionCount > 0)
                      <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 mb-2 mr-2 btn-locked btn-icon" data-name="{{ $dept->name }}">
                        <i class="fas fa-lock"></i><span class="d-none d-lg-inline"> Locked</span>
                      </button>
                    @else
                      <form action="{{ route('admin.departments.destroy', $dept) }}" method="POST" class="d-inline delete-form mb-2 mr-2">
                        @csrf @method('DELETE')
                        <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 btn-delete btn-icon" data-name="{{ $dept->name }}">
                          <i class="fas fa-trash"></i><span class="d-none d-lg-inline"> Delete</span>
                        </button>
                      </form>
                    @endif
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- ===== Mobile: Card list view ===== --}}
      <div class="d-md-none">
        <div class="list-group">
          @foreach($departments as $dept)
            @php($sectionCount = $dept->sections_count ?? $dept->sections->count())
            <div class="list-group-item">
              <div class="d-flex justify-content-between align-items-start">
                <div class="mr-2">
                  <div class="font-weight-bold">{{ $dept->name }}</div>
                  <small class="text-muted">
                    <span class="badge badge-info badge-pill">{{ $sectionCount }} Section</span>
                  </small>
                </div>
                <span class="text-secondary">#{{ $rowStart + $loop->index }}</span>

              </div>

              <div class="mt-3 d-flex flex-wrap">
                <a href="{{ route('admin.sections.index', ['filter_department' => $dept->id]) }}" class="btn btn-info btn-sm rounded-pill px-3 mr-2 mb-2 w-100">
                  <i class="fas fa-list"></i> Show Sections
                </a>
                <a href="{{ route('admin.departments.edit', $dept) }}" class="btn btn-warning btn-sm rounded-pill px-3 mr-2 mb-2 w-100">
                  <i class="fas fa-edit"></i> Edit
                </a>
                @if($sectionCount > 0)
                  <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 mr-2 mb-2 w-100 btn-locked" data-name="{{ $dept->name }}">
                    <i class="fas fa-lock"></i> Locked
                  </button>
                @else
                  <form action="{{ route('admin.departments.destroy', $dept) }}" method="POST" class="delete-form w-100 mr-2 mb-2">
                    @csrf @method('DELETE')
                    <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 w-100 btn-delete" data-name="{{ $dept->name }}">
                      <i class="fas fa-trash"></i> Delete
                    </button>
                  </form>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      </div>

      {{-- Pagination --}}
      @if(method_exists($departments,'links'))
        <div class="px-3 px-lg-4 py-3">
          {{ $departments->appends(['q' => $q])->links() }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // SweetAlert delete
    document.body.addEventListener('click', function(e) {
      const btn = e.target.closest('.btn-delete');
      if (!btn) return;
      const form = btn.closest('form.delete-form');
      const deptName = btn.dataset.name;
      Swal.fire({
        title: `Yakin ingin menghapus "${deptName}"?`,
        text: 'Semua data terkait akan terhapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
      }).then(result => {
        if (result.isConfirmed && form) form.submit();
      });
    });

    // SweetAlert locked
    document.body.addEventListener('click', function(e) {
      const btn = e.target.closest('.btn-locked');
      if (!btn) return;
      const name = btn.dataset.name;
      Swal.fire({
        title: `Tidak bisa menghapus "${name}"`,
        text: 'Masih terdapat Section di dalam Department ini.',
        icon: 'info',
        confirmButtonText: 'Mengerti'
      });
    });

    // Toast sukses
    @if(session('success'))
    Swal.fire({
      icon: 'success',
      title: 'Berhasil',
      text: @json(session('success')),
      timer: 2200,
      toast: true,
      position: 'top-end',
      showConfirmButton: false
    });
    @endif
  });
</script>

{{-- (Opsional) DataTables — jika dipakai, pastikan assets DT sudah di-include di layout --}}
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const tableEl = document.getElementById('departments-table');
    if (tableEl && window.jQuery && jQuery.fn.DataTable) {
      const dt = jQuery(tableEl).DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [[1, 'asc']],
        columnDefs: [
          { orderable: false, targets: [0,3] },
          { searchable: false, targets: [0,2,3] }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json' }
      });
      jQuery('#addDept').on('shown.bs.collapse hidden.bs.collapse', () => dt.columns.adjust().responsive.recalc());
      window.addEventListener('resize', () => dt.columns.adjust());
    }
  });
</script>
@endpush
