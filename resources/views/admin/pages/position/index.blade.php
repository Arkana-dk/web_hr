{{-- resources/views/admin/positions/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Daftar Posisi')

@push('styles')
<style>
  /* ===== Hero Card (konsisten) ===== */
  .hero-card{
    background:
      radial-gradient(1200px 400px at 10% -10%, rgba(59,130,246,.25), transparent 60%),
      radial-gradient(1200px 400px at 90% -10%, rgba(236,72,153,.25), transparent 60%),
      linear-gradient(135deg, #ffffff, #f8fafc);
    border:1px solid rgba(0,0,0,.05);
    border-radius:.75rem;
    backdrop-filter: blur(4px);
  }
  .hero-card .shine{ position:absolute; inset:0; pointer-events:none;
    background: radial-gradient(400px 120px at 80% 10%, rgba(255,255,255,.5), transparent 60%);
  }
  .hero-emoji{ font-size:56px; line-height:1; filter:drop-shadow(0 10px 18px rgba(0,0,0,.1)); transform: translateY(2px); }

  /* ===== Chips ===== */
  .chip{
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.35rem .6rem; border-radius:999px; font-weight:600; font-size:.78rem;
    background:#eef2ff; color:#3730a3;
  }
  .chip.alt{ background:#ecfeff; color:#155e75; }

  /* ===== Table polish ===== */
  .table thead th { position: sticky; top: 0; z-index: 1; background: #f8fafc; }
  .table-hover tbody tr:hover { background: #f9fbff; }
  .table td, .table th { vertical-align: middle; }
</style>
@endpush

@section('content')
@php
  $q            = request('q');
  $sectionId    = request('section_id');
  $rowStart     = method_exists($positions,'firstItem') ? $positions->firstItem() : 1;   // aman utk paginator/collection
  $totalPos     = method_exists($positions,'total') ? $positions->total() : $positions->count();
  $selectedSec  = ($sections ?? collect())->firstWhere('id', (int)$sectionId);
@endphp

<div class="container-fluid">

  {{-- ===== Hero Card ===== --}}
  <div class="card hero-card shadow-sm mb-4 position-relative overflow-hidden">
    <div class="shine"></div>
    <div class="card-body py-4 px-4 px-lg-5">
      <div class="d-flex flex-column flex-lg-row align-items-lg-center">
        <div class="flex-grow-1">
          <div class="d-flex align-items-center mb-2">
            <span class="hero-emoji mr-3">📋</span>
            <div>
              <h1 class="h4 mb-1 text-gray-800">Daftar Posisi Jabatan</h1>
              <p class="mb-0 text-muted">Kelola posisi, tautkan ke Section, dan pantau jumlah pegawai.</p>
            </div>
          </div>
          <div class="d-flex flex-wrap align-items-center mt-3" style="gap:.5rem 1rem">
            <span class="chip"><i class="fas fa-layer-group"></i> Total: {{ number_format($totalPos) }}</span>
            @if($selectedSec)
              <span class="chip alt"><i class="fas fa-stream"></i> Section: {{ $selectedSec->name }}</span>
            @endif
            @if($q)
              <span class="chip alt"><i class="fas fa-filter"></i> Filter: “{{ $q }}”</span>
            @endif
          </div>
        </div>
        <div class="mt-3 mt-lg-0 ml-lg-4">
          <button class="btn btn-primary btn-icon shadow-sm" data-toggle="collapse" data-target="#addPos" aria-expanded="false" aria-controls="addPos">
            <i class="fas fa-plus"></i><span> Tambah Posisi</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- ===== Toolbar: Search + Filter Section ===== --}}
  <div class="card border-0 mb-3">
    <div class="card-body py-3 px-3 px-lg-4">
      <form method="GET" action="{{ route('admin.positions.index') }}">
        <div class="form-row align-items-end">
          <div class="col-12 col-md-5 mb-2">
            <label class="font-weight-bold" for="q">Cari Posisi</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
              </div>
              <input type="text" name="q" id="q" value="{{ $q }}" class="form-control" placeholder="Ketik nama posisi…">
              @if($q)
                <div class="input-group-append">
                  <a href="{{ route('admin.positions.index', ['section_id' => $sectionId ?: null]) }}" class="btn btn-outline-secondary">Reset</a>
                </div>
              @endif
            </div>
          </div>
          <div class="col-12 col-md-5 mb-2">
            <label class="font-weight-bold" for="section_id">Filter Section</label>
            <select name="section_id" id="section_id" class="form-control" onchange="this.form.submit()">
              <option value="">— Semua Section —</option>
              @foreach($sections as $sec)
                <option value="{{ $sec->id }}" {{ (string)$sectionId === (string)$sec->id ? 'selected' : '' }}>
                  {{ $sec->name }} @if($sec->department?->name) — {{ $sec->department->name }} @endif
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-2 mb-2 text-md-right">
            <label class="d-none d-md-block font-weight-bold invisible">Terapkan</label>
            <button type="submit" class="btn btn-outline-primary btn-block"><i class="fas fa-filter mr-1"></i> Terapkan</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- ===== Card: Form + Tabel ===== --}}
  <div class="card shadow mb-4">
    <div class="card-body">

      {{-- Form Tambah Posisi (Collapse) --}}
      <div class="collapse mb-4" id="addPos">
        <form action="{{ route('admin.positions.store') }}" method="POST" id="position-create-form" novalidate>
          @csrf

          <div class="form-group">
            <label for="name"><strong>Nama Posisi</strong></label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                   placeholder="Contoh: Supervisor" required minlength="2" maxlength="100">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group">
            <label class="d-flex justify-content-between align-items-center">
              <strong>Bagian Terkait (Sections)</strong>
              <span class="small text-muted">Pilih minimal 1 bila diperlukan</span>
            </label>

            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" id="select-all-sections">
              <label class="form-check-label font-weight-bold" for="select-all-sections">Pilih Semua</label>
            </div>

            <div class="row">
              @foreach($sections as $section)
                <div class="col-12 col-sm-6 col-md-4 mb-2">
                  <div class="border rounded p-2 h-100">
                    <div class="form-check">
                      <input class="form-check-input section-checkbox" type="checkbox" name="section_ids[]" value="{{ $section->id }}" id="section-{{ $section->id }}">
                      <label class="form-check-label" for="section-{{ $section->id }}">
                        <div class="font-weight-semibold">{{ $section->name }}</div>
                        <small class="text-muted">{{ $section->department->name ?? '-' }}</small>
                      </label>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>

          <div class="d-flex flex-column flex-sm-row">
            <button type="submit" class="btn btn-success rounded-pill px-4 mr-sm-2" id="btn-create">
              <span class="btn-text">Simpan Posisi</span>
              <span class="spinner-border spinner-border-sm align-text-bottom d-none" role="status" aria-hidden="true"></span>
            </button>
            <button type="reset" class="btn btn-outline-secondary rounded-pill px-4 mt-2 mt-sm-0">Reset</button>
          </div>
        </form>
      </div>

      {{-- Desktop/Tablet: DataTable --}}
      <div class="table-responsive-md d-none d-md-block">
        <table class="table table-bordered table-hover align-middle" id="positions-table" width="100%">
          <thead class="thead-light text-center">
            <tr>
              <th style="width:6%">#</th>
              <th class="text-left">Nama Posisi</th>
              <th class="text-left" style="width:30%">Sections</th>
              <th style="width:14%">Pegawai</th>
              <th style="width:20%">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($positions as $pos)
              @php
                $empCount   = $pos->employees_count ?? ($pos->employees->count() ?? 0);
                $posSections = $pos->sections ?? collect();           // pastikan eager-load di controller untuk terbaik
                $firstThree  = $posSections->take(3);
                $extraCount  = max(0, $posSections->count() - 3);
              @endphp
              <tr data-id="{{ $pos->id }}">
                <td class="text-center">{{ $rowStart + $loop->index }}</td>
                <td class="text-truncate" style="max-width: 320px">{{ $pos->name }}</td>
                <td class="text-truncate">
                  @forelse($firstThree as $s)
                    <span class="badge badge-secondary mb-1">{{ $s->name }}</span>
                  @empty
                    <span class="text-muted small">(Belum ditautkan)</span>
                  @endforelse
                  @if($extraCount > 0)
                    <span class="badge badge-light">+{{ $extraCount }} lainnya</span>
                  @endif
                </td>
                <td class="text-center">
                  <span class="badge badge-info badge-pill">{{ $empCount }}</span>
                </td>
                <td class="text-center">
                  <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="{{ route('admin.positions.show', $pos) }}" class="btn btn-info btn-sm rounded-pill px-3 mr-2 mb-2" title="Detail">
                      <i class="fas fa-users"></i> <span class="d-none d-lg-inline">Detail</span>
                    </a>
                    <a href="{{ route('admin.positions.edit', $pos) }}" class="btn btn-warning btn-sm rounded-pill px-3 mr-2 mb-2" title="Edit">
                      <i class="fas fa-edit"></i> <span class="d-none d-lg-inline">Edit</span>
                    </a>
                    <form action="{{ route('admin.positions.destroy', $pos) }}" method="POST" class="d-inline delete-form mb-2">
                      @csrf @method('DELETE')
                      <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 btn-delete" title="Hapus"
                              data-action="{{ route('admin.positions.destroy', $pos) }}" data-token="{{ csrf_token() }}">
                        <i class="fas fa-trash"></i> <span class="d-none d-lg-inline">Hapus</span>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Mobile: Card list --}}
      <div class="d-md-none">
        <div class="list-group">
          @foreach($positions as $pos)
            @php
              $empCount   = $pos->employees_count ?? ($pos->employees->count() ?? 0);
              $posSections = $pos->sections ?? collect();
              $firstThree  = $posSections->take(3);
              $extraCount  = max(0, $posSections->count() - 3);
            @endphp
            <div class="list-group-item" data-id="{{ $pos->id }}">
              <div class="d-flex justify-content-between align-items-start">
                <div class="mr-2">
                  <div class="font-weight-bold">{{ $pos->name }}</div>
                  <div class="mt-1">
                    @forelse($firstThree as $s)
                      <span class="badge badge-secondary mb-1">{{ $s->name }}</span>
                    @empty
                      <span class="text-muted small">(Belum ditautkan)</span>
                    @endforelse
                    @if($extraCount > 0)
                      <span class="badge badge-light">+{{ $extraCount }} lainnya</span>
                    @endif
                  </div>
                  <div class="mt-1"><span class="badge badge-info badge-pill">{{ $empCount }} Pegawai</span></div>
                </div>
                <span class="text-secondary">#{{ $rowStart + $loop->index }}</span>
              </div>
              <div class="mt-3 d-flex flex-wrap">
                <a href="{{ route('admin.positions.show', $pos) }}" class="btn btn-info btn-sm rounded-pill px-3 mr-2 mb-2 w-100">Detail</a>
                <a href="{{ route('admin.positions.edit', $pos) }}" class="btn btn-warning btn-sm rounded-pill px-3 mr-2 mb-2 w-100">Edit</a>
                <form action="{{ route('admin.positions.destroy', $pos) }}" method="POST" class="delete-form w-100 mr-2 mb-2">
                  @csrf @method('DELETE')
                  <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 w-100 btn-delete"
                          data-action="{{ route('admin.positions.destroy', $pos) }}" data-token="{{ csrf_token() }}">Hapus</button>
                </form>
              </div>
            </div>
          @endforeach
        </div>
      </div>

      {{-- Pagination (jika pakai paginate di controller) --}}
      @if(method_exists($positions,'links'))
        <div class="px-3 px-lg-4 pt-3">
          {{ $positions->appends(['q'=>$q,'section_id'=>$sectionId ?: null])->links() }}
        </div>
      @endif

    </div>
  </div>
</div>
@endsection

@push('styles')
  {{-- DataTables Bootstrap 4 + Responsive CSS --}}
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
@endpush

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  {{-- DataTables JS --}}
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function(){
      // Select all / indeterminate
      const selectAll = document.getElementById('select-all-sections');
      const sectionCbs = Array.from(document.querySelectorAll('.section-checkbox'));
      function refreshSelectAllState(){
        const checked = sectionCbs.filter(cb => cb.checked).length;
        if (!selectAll) return;
        if (checked === 0) { selectAll.indeterminate = false; selectAll.checked = false; }
        else if (checked === sectionCbs.length) { selectAll.indeterminate = false; selectAll.checked = true; }
        else { selectAll.indeterminate = true; }
      }
      if (selectAll) {
        selectAll.addEventListener('change', () => sectionCbs.forEach(cb => cb.checked = selectAll.checked));
      }
      sectionCbs.forEach(cb => cb.addEventListener('change', refreshSelectAllState));
      refreshSelectAllState();

      // Prevent double submit
      const formCreate = document.getElementById('position-create-form');
      const btnCreate = document.getElementById('btn-create');
      if (formCreate && btnCreate) {
        formCreate.addEventListener('submit', function(){
          const spinner = btnCreate.querySelector('.spinner-border');
          const text = btnCreate.querySelector('.btn-text');
          btnCreate.disabled = true;
          if (spinner) spinner.classList.remove('d-none');
          if (text) text.classList.add('opacity-75');
        });
      }

      // Toasts
      @if(session('success'))
      Swal.fire({ icon:'success', title:'Berhasil!', text:@json(session('success')), timer:2200, toast:true, position:'top-end', showConfirmButton:false });
      @endif
      @if ($errors->any())
      Swal.fire({ icon:'error', title:'Gagal!', html:`{!! implode('<br>', $errors->all()) !!}` });
      @endif

      // Delete confirmation (delegated; supports responsive row)
      document.body.addEventListener('click', function(e){
        const delBtn = e.target.closest('.btn-delete');
        if (!delBtn) return;
        e.preventDefault();
        Swal.fire({
          title: 'Yakin ingin menghapus?',
          text: 'Data yang dihapus tidak bisa dikembalikan.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Ya, hapus',
          cancelButtonText: 'Batal'
        }).then(result => {
          if (result.isConfirmed) {
            const form = delBtn.closest('form.delete-form');
            if (form) { form.submit(); return; }
            // fallback jika terlepas dari form (child row)
            const action = delBtn.dataset.action;
            const token = delBtn.dataset.token || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (action && token) {
              const tmp = document.createElement('form');
              tmp.method = 'POST'; tmp.action = action; tmp.style.display = 'none';
              const t = document.createElement('input'); t.name = '_token'; t.value = token; tmp.appendChild(t);
              const m = document.createElement('input'); m.name = '_method'; m.value = 'DELETE'; tmp.appendChild(m);
              document.body.appendChild(tmp); tmp.submit();
            }
          }
        });
      });

      // DataTables
      const tableEl = document.getElementById('positions-table');
      if (tableEl && window.jQuery) {
        const dt = jQuery(tableEl).DataTable({
          responsive: true,
          pageLength: 10,
          lengthMenu: [10, 25, 50, 100],
          order: [[1, 'asc']],
          columnDefs: [
            { orderable: false, targets: [0, 2, 4] }, // sections kolom bisa bikin lebar; matikan sort
            { searchable: false, targets: [0, 2, 3, 4] }
          ],
          dom: "<'row'<'col-12'tr>>" + "<'row align-items-center px-3 pb-2'<'col-sm-6'i><'col-sm-6 text-sm-right'p>>",
          language: { url:'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json', emptyTable:'Belum ada data posisi.' }
        });
        window.addEventListener('resize', () => dt.columns.adjust());
      }
    });
  </script>
@endpush
