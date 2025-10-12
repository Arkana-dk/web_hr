@extends('layouts.master')

@section('title','Data Pegawai')

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
        <h2 class="hero-title mb-1">Data Pegawai</h2>
        <div class="hero-meta">
          Total: <strong>{{ $employees->total() }}</strong>
          <span class="d-none d-md-inline">•</span>
          <span class="d-block d-md-inline">Tampil: <strong>{{ $employees->count() }}</strong> item (halaman ini)</span>
        </div>
      </div>

      {{-- Actions (pill buttons) --}}
      <div class="hero-actions mt-3 mt-md-0">
        <a href="{{ route('admin.employees.create') }}" class="btn btn-primary btn-pill btn-elev">
          <i class="fas fa-plus mr-1"></i> Tambah Pegawai
        </a>

        <form action="{{ route('admin.employee.import.form') }}" method="GET" class="d-inline-block">
          <button class="btn btn-success btn-pill btn-elev" type="submit">
            <i class="fas fa-file-upload mr-1"></i> Upload Excel
          </button>
        </form>

        <button id="bulkDeleteTrigger" class="btn btn-outline-danger btn-pill">
          <i class="fas fa-trash-alt mr-1"></i> Hapus Beberapa
        </button>
      </div>
    </div>
  </div>

  {{-- ================= FILTER (mini-hero) ================= --}}
  <form method="GET" action="{{ request()->url() }}" class="card filter-card mb-3">
    <div class="card-body">
      <div class="filter-grid pill">
        <div>
          <label class="filter-label">Cari</label>
          <input type="text" name="search" class="form-control"
                 placeholder="Cari nama, nomor karyawan, atau email…"
                 value="{{ request('search', $search ?? '') }}">
        </div>

        <div class="ml-md-auto">
          <div class="toolbar">
            <button class="btn btn-primary btn-pill" type="submit">
              <i class="fas fa-search mr-1"></i> Cari
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
              <th class="bulk-checkbox-header d-none" style="width:36px">
                <input type="checkbox" id="checkAll">
              </th>
              <th style="width:64px">No</th>
              <th>Foto</th>
              <th>Nama</th>
              <th>Nomor karyawan</th>
              <th>Email</th>
              <th>No Handphone</th>
              <th>Departement</th>
              <th>Seksi</th>
              <th>Grup</th>
              <th>Posisi</th>
              <th style="width:160px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($employees as $employee)
              <tr>
                <td class="bulk-checkbox-cell d-none">
                  <input type="checkbox" name="selected_ids[]" value="{{ $employee->id }}" form="bulk-delete-form">
                </td>
                <td>{{ $employees->firstItem() + $loop->index }}</td>
                <td>
                  <div class="avatar">
                    <img
                      src="{{ $employee->photo ? asset('storage/'.$employee->photo) : asset('images/default-user.png') }}"
                      alt="Foto {{ $employee->name }}">
                  </div>
                </td>
                <td class="fw-semibold">{{ $employee->name }}</td>
                <td>{{ $employee->employee_number }}</td>
                <td>{{ $employee->email }}</td>
                <td>{{ $employee->phone ?? '—' }}</td>
                <td>{{ $employee->department->name ?? '—' }}</td>
                <td>{{ $employee->section->name ?? '—' }}</td>
                <td>{{ $employee->group->name ?? '—' }}</td>
                <td>{{ $employee->position->name ?? '—' }}</td>
                <td>
                  <div class="d-flex flex-wrap" style="gap:.35rem">
                    <a href="{{ route('admin.employees.show', $employee) }}" class="btn btn-info btn-sm btn-pill">
                      <i class="fas fa-eye"></i>
                    </a>
                    <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-warning btn-sm btn-pill">
                      <i class="fas fa-pencil-alt"></i>
                    </a>
                    <form action="{{ route('admin.employees.destroy', $employee) }}" method="POST" class="d-inline delete-form">
                      @csrf @method('DELETE')
                      <button type="button" class="btn btn-danger btn-sm btn-pill btn-delete">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="12" class="text-center py-4">Tidak ada data.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center">
      <div class="small text-muted">
        Menampilkan {{ $employees->firstItem() }}–{{ $employees->lastItem() }} dari {{ $employees->total() }} data
      </div>
      {{-- withQueryString agar parameter pencarian tetap terbawa saat paging --}}
      {{ $employees->withQueryString()->links('pagination::bootstrap-4') }}
    </div>
  </div>

  {{-- Bulk Delete form (tetap dipakai, hanya dipindah ke bawah) --}}
  <form action="{{ route('admin.employee.bulk-delete') }}" method="POST" id="bulk-delete-form" class="mt-2">
    @csrf @method('DELETE')
    <button type="submit" class="btn btn-danger btn-sm d-none" id="bulk-delete-button">
      <i class="fas fa-trash"></i> Hapus Terpilih
    </button>
  </form>

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

  /* Tabel */
  .table-sticky thead th{ position:sticky; top:0; z-index:2; background:#fff; }
  .table-nowrap th, .table-nowrap td{ white-space:nowrap; }
  .table td, .table th{ vertical-align:middle; }

  /* Avatar */
  .avatar{ width:64px; height:64px; overflow:hidden; border-radius:50%; border:2px solid #e9ecef; }
  .avatar img{ width:100%; height:100%; object-fit:cover; }

  /* Helpers */
  .d-none{ display:none!important; }
  .pagination .page-link{ padding:.25rem .5rem; font-size:.875rem; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Toggle bulk delete mode
  document.getElementById('bulkDeleteTrigger').addEventListener('click', function () {
    document.querySelectorAll('.bulk-checkbox-header, .bulk-checkbox-cell').forEach(el => el.classList.remove('d-none'));
    document.getElementById('bulk-delete-button').classList.remove('d-none');
  });

  // Check all
  document.getElementById('checkAll')?.addEventListener('click', function () {
    document.querySelectorAll('input[name="selected_ids[]"]').forEach(cb => cb.checked = this.checked);
  });

  // Confirm single delete
  document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function () {
      Swal.fire({
        title: 'Yakin ingin menghapus?',
        text: 'Data pegawai akan dihapus permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
      }).then(result => {
        if (result.isConfirmed) this.closest('form.delete-form').submit();
      });
    });
  });

  // Validate bulk delete (at submit)
  document.getElementById('bulk-delete-form').addEventListener('submit', function(e) {
    const checked = document.querySelectorAll('input[name="selected_ids[]"]:checked');
    if (!checked.length) {
      e.preventDefault();
      Swal.fire('Peringatan', 'Pilih minimal satu data untuk dihapus.', 'warning');
    }
  });
</script>
@endpush
