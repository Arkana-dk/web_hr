{{-- resources/views/admin/pages/sections/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Manajemen Seksi')

@push('styles')
<style>
  /* ===== Hero Card (selaras halaman Transport) ===== */
  .hero-card {
    background:
      radial-gradient(1200px 400px at 10% -10%, rgba(59,130,246,.25), transparent 60%),
      radial-gradient(1200px 400px at 90% -10%, rgba(236,72,153,.25), transparent 60%),
      linear-gradient(135deg, #ffffff, #f8fafc);
    border: 1px solid rgba(0,0,0,.05);
    backdrop-filter: blur(4px);
    border-radius: .75rem;
  }
  .hero-card .shine { position:absolute; inset:0; background: radial-gradient(400px 120px at 80% 10%, rgba(255,255,255,.5), transparent 60%); pointer-events:none; }
  .hero-emoji { font-size:56px; line-height:1; filter:drop-shadow(0 10px 18px rgba(0,0,0,.1)); transform: translateY(2px); }

  /* ===== Chips ===== */
  .chip {
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.35rem .6rem; border-radius:999px; font-weight:600; font-size:.78rem;
    background:#eef2ff; color:#3730a3;
  }
  .chip.alt { background:#ecfeff; color:#155e75; }

  /* ===== Table polish ===== */
  .table thead th { position: sticky; top: 0; z-index: 1; background: #f8fafc; }
  .table-hover tbody tr:hover { background: #f9fbff; }
  .table td, .table th { vertical-align: middle; }
  .badge-soft { background:#eef2ff; color:#3730a3; border-radius:999px; padding:.2rem .5rem; font-size:.75rem; font-weight:600; }

  /* ===== Form highlight & row highlight ===== */
  .row-highlight { transition: background-color 1.5s ease; }
  #section-form-card.form-card-highlight { box-shadow: 0 0 0 3px rgba(59,130,246,.35); }

  /* ===== Buttons ===== */
  .btn-icon { display:inline-flex; align-items:center; gap:.4rem; }

  /* ===== Empty state (mobile) ===== */
  .empty-illustration { font-size: 64px; opacity:.7; line-height:1; }
</style>
@endpush

@section('content')
@php
  $q = request('q');
  $deptFilterId = (int) request('department_id');
  $selectedDept = ($departments ?? collect())->firstWhere('id', $deptFilterId);
  $totalSections = method_exists($sections,'total') ? $sections->total() : $sections->count();
  $rowStart = method_exists($sections,'firstItem') ? $sections->firstItem() : 1;  // aman untuk paginator/collection
@endphp

<div class="container-fluid">

  {{-- Flash --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
      <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
      <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
    </div>
  @endif

  {{-- ===== Hero Card ===== --}}
  <div class="card hero-card shadow-sm mb-4 position-relative overflow-hidden">
    <div class="shine"></div>
    <div class="card-body py-4 px-4 px-lg-5">
      <div class="d-flex flex-column flex-lg-row align-items-lg-center">
        <div class="flex-grow-1">
          <div class="d-flex align-items-center mb-2">
            <span class="hero-emoji mr-3">🗂️</span>
            <div>
              <h1 class="h4 mb-1 text-gray-800">Manajemen Seksi</h1>
              <p class="mb-0 text-muted">Kelola seksi, hubungkan ke departemen dan posisi jabatan tanpa pusing.</p>
            </div>
          </div>

          <div class="d-flex flex-wrap align-items-center mt-3" style="gap:.5rem 1rem">
            <span class="chip"><i class="fas fa-layer-group"></i> Total: {{ number_format($totalSections) }}</span>
            @if($selectedDept)
              <span class="chip alt"><i class="fas fa-building"></i> Dept: {{ $selectedDept->name }}</span>
            @endif
            @if($q)
              <span class="chip alt"><i class="fas fa-filter"></i> Filter: “{{ $q }}”</span>
            @endif
          </div>
        </div>

        <div class="mt-3 mt-lg-0 ml-lg-4">
          <a href="#section-form-card" class="btn btn-primary btn-icon shadow-sm" onclick="document.getElementById('name')?.focus()">
            <i class="fas fa-plus"></i><span> Tambah Seksi</span>
          </a>
          <a href="{{ route('admin.positions.index') }}" class="btn btn-outline-primary btn-icon ml-2">
            <i class="fas fa-briefcase"></i><span> Kelola Posisi</span>
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- ===== Toolbar: Search + Filter Dept (selaras Transport) ===== --}}
  <div class="card border-0 mb-3">
    <div class="card-body py-3 px-3 px-lg-4">
      <form method="GET" action="{{ route('admin.sections.index') }}" id="filter-form">
        <div class="form-row align-items-end">
          <div class="col-12 col-md-5 mb-2">
            <label class="font-weight-bold" for="q">Cari Seksi</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
              </div>
              <input type="text" name="q" id="q" value="{{ $q }}" class="form-control" placeholder="Ketik nama seksi…">
              @if($q)
                <div class="input-group-append">
                  <a href="{{ route('admin.sections.index', ['department_id' => $deptFilterId ?: null]) }}" class="btn btn-outline-secondary">Reset</a>
                </div>
              @endif
            </div>
          </div>

          <div class="col-12 col-md-5 mb-2">
            <label class="font-weight-bold" for="department_id">Filter Departemen</label>
            <select name="department_id" id="department_id" class="form-control" onchange="this.form.submit()">
              <option value="">— Semua Departemen —</option>
              @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ $deptFilterId === $dept->id ? 'selected' : '' }}>
                  {{ $dept->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-12 col-md-2 mb-2 text-md-right">
            <label class="d-none d-md-block font-weight-bold invisible">Tampilkan</label>
            <button type="submit" class="btn btn-outline-primary btn-block btn-icon"><i class="fas fa-filter"></i><span> Terapkan</span></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="row">
    {{-- ===== FORM ===== --}}
    <div class="col-md-4">
      <div id="section-form-card" class="card shadow">
        <div class="card-header {{ isset($editSection) ? 'bg-warning' : 'bg-info' }} text-white d-flex justify-content-between align-items-center">
          <strong>{{ isset($editSection) ? '✏️ Edit Seksi' : '➕ Tambah Seksi' }}</strong>
          @if(isset($editSection))
            <a href="{{ route('admin.sections.index', ['department_id' => $deptFilterId ?: null, 'q' => $q ?: null]) }}" class="btn btn-light btn-sm rounded-pill px-3">Batal</a>
          @endif
        </div>
        <div class="card-body">
          <form action="{{ isset($editSection) ? route('admin.sections.update', $editSection) : route('admin.sections.store') }}" method="POST" id="section-form" novalidate>
            @csrf
            @if(isset($editSection)) @method('PUT') @endif

            <div class="form-group mb-3">
              <label for="name">Nama Seksi</label>
              <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                     value="{{ old('name', $editSection->name ?? '') }}" required minlength="2" maxlength="100"
                     placeholder="Contoh: Produksi A" @if(isset($editSection)) autofocus @endif>
              @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group mb-3">
              <label for="department_id_input">Departemen</label>
              <select id="department_id_input" name="department_id" class="form-control @error('department_id') is-invalid @enderror" required>
                <option value="">— Pilih Departemen —</option>
                @foreach($departments as $department)
                  <option value="{{ $department->id }}"
                          {{ old('department_id', $editSection->department_id ?? $deptFilterId) == $department->id ? 'selected' : '' }}>
                    {{ $department->name }}
                  </option>
                @endforeach
              </select>
              @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            @php
              $selectedPositions = old('position_ids', isset($editSection) ? $editSection->positions->pluck('id')->all() : []);
              if (!is_array($selectedPositions)) $selectedPositions = [];
            @endphp
            <div class="form-group mb-3">
              <div class="d-flex justify-content-between align-items-center">
                <label class="mb-0" for="position_ids">Posisi Jabatan</label>
                <a href="{{ route('admin.positions.index') }}" class="btn btn-sm btn-outline-primary rounded-pill">
                  <i class="fas fa-briefcase"></i> Kelola Posisi
                </a>
              </div>
              <select id="position_ids" name="position_ids[]" class="form-control mt-2 @error('position_ids') is-invalid @enderror" multiple>
                @foreach($positions as $position)
                  <option value="{{ $position->id }}" {{ in_array($position->id, $selectedPositions, true) ? 'selected' : '' }}>
                    {{ $position->name }}
                  </option>
                @endforeach
              </select>
              <small class="text-muted">Tips: Ctrl (Windows) / Cmd (Mac) untuk multi-pilih</small>
              @error('position_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="d-flex flex-column flex-sm-row">
              <button type="submit" class="btn {{ isset($editSection) ? 'btn-warning' : 'btn-success' }} rounded-pill px-4 w-100 w-sm-auto mr-sm-2" id="btn-save">
                <span class="btn-text">{{ isset($editSection) ? '💾 Simpan Perubahan' : '📤 Tambahkan' }}</span>
                <span class="spinner-border spinner-border-sm align-text-bottom d-none" role="status" aria-hidden="true"></span>
              </button>
              @if(!isset($editSection))
                <button type="reset" class="btn btn-outline-secondary rounded-pill px-4 mt-2 mt-sm-0 w-100 w-sm-auto">Reset</button>
              @endif
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- ===== LIST ===== --}}
    <div class="col-md-8">
      <div class="card shadow">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <strong>📚 Daftar Seksi</strong>
          <span class="text-muted small">Menampilkan {{ method_exists($sections,'count') ? $sections->count() : count($sections) }} item</span>
        </div>
        <div class="card-body">

          {{-- Desktop/Tablet: DataTable --}}
          <div class="table-responsive-md d-none d-md-block">
            <table class="table table-bordered table-hover align-middle" id="sections-table" width="100%">
              <thead class="thead-light text-center">
                <tr>
                  <th style="width:5%">#</th>
                  <th>Nama Seksi</th>
                  <th>Departemen</th>
                  <th>Posisi Jabatan</th>
                  <th style="width:14%">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @foreach($sections as $section)
                  @php
                    $posCount   = $section->positions_count ?? $section->positions->count();
                    $firstThree = $section->positions->take(3);
                  @endphp
                  <tr data-id="{{ $section->id }}">
                    <td class="text-center">{{ $rowStart + $loop->index }}</td>
                    <td class="text-truncate" style="max-width:280px"><strong>{{ $section->name }}</strong></td>
                    <td class="text-center"><span class="badge-soft">{{ $section->department->name ?? '-' }}</span></td>
                    <td>
                      @forelse($firstThree as $position)
                        <span class="badge badge-secondary mb-1">{{ $position->name }}</span>
                      @empty
                        <span class="text-muted small">(Belum ada posisi)</span>
                      @endforelse
                      @if($posCount > 3)
                        <span class="badge badge-light">+{{ $posCount - 3 }} lainnya</span>
                      @endif
                    </td>
                    <td class="text-center">
                      <div class="d-flex flex-wrap justify-content-center">
                        <a href="{{ route('admin.sections.index', ['edit' => $section->id, 'department_id' => $deptFilterId ?: null, 'q' => $q ?: null]) }}"
                           class="btn btn-warning btn-sm rounded-pill px-3 mr-2 mb-2" title="Edit">✏️</a>

                        @if($posCount > 0)
                          <button type="button"
                                  class="btn btn-danger btn-sm rounded-pill px-3 mb-2"
                                  disabled
                                  title="Tidak bisa dihapus: terkait {{ $posCount }} posisi. Lepaskan dulu.">
                            🗑️
                          </button>
                        @else
                          <form action="{{ route('admin.sections.destroy', $section) }}" method="POST" class="d-inline delete-form mb-2">
                            @csrf @method('DELETE')
                            <button type="button"
                                    class="btn btn-danger btn-sm rounded-pill px-3 btn-delete"
                                    data-action="{{ route('admin.sections.destroy', $section) }}"
                                    data-token="{{ csrf_token() }}"
                                    title="Hapus">
                              🗑️
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

          {{-- Mobile: Card list --}}
          <div class="d-md-none">
            <div class="list-group">
              @if($sections->isEmpty())
                <div class="text-center text-muted py-5">
                  <div class="empty-illustration mb-2">🗂️</div>
                  Belum ada data seksi
                </div>
              @else
                @foreach($sections as $section)
                  @php
                    $posCount   = $section->positions_count ?? $section->positions->count();
                    $firstThree = $section->positions->take(3);
                  @endphp
                  <div class="list-group-item" data-id="{{ $section->id }}">
                    <div class="d-flex justify-content-between align-items-start">
                      <div class="mr-2">
                        <div class="font-weight-bold">{{ $section->name }}</div>
                        <div class="mt-1">
                          <span class="badge-soft">{{ $section->department->name ?? '-' }}</span>
                        </div>
                      </div>
                      <span class="text-secondary">#{{ $rowStart + $loop->index }}</span>
                    </div>

                    <div class="mt-2">
                      @forelse($firstThree as $position)
                        <span class="badge badge-secondary mb-1">{{ $position->name }}</span>
                      @empty
                        <span class="text-muted small">(Belum ada posisi)</span>
                      @endforelse
                      @if($posCount > 3)
                        <span class="badge badge-light">+{{ $posCount - 3 }} lainnya</span>
                      @endif
                    </div>

                    <div class="mt-3 d-flex flex-wrap">
                      <a href="{{ route('admin.sections.index', ['edit' => $section->id, 'department_id' => $deptFilterId ?: null, 'q' => $q ?: null]) }}"
                         class="btn btn-warning btn-sm rounded-pill px-3 mr-2 mb-2 w-100">✏️ Edit</a>

                      @if($posCount > 0)
                        <button type="button"
                                class="btn btn-danger btn-sm rounded-pill px-3 w-100 mb-2"
                                disabled
                                title="Tidak bisa dihapus: terkait {{ $posCount }} posisi. Lepaskan dulu.">
                          🗑️ Hapus
                        </button>
                        <small class="text-muted w-100">Terkait {{ $posCount }} posisi</small>
                      @else
                        <form action="{{ route('admin.sections.destroy', $section) }}" method="POST" class="delete-form w-100 mr-2 mb-2">
                          @csrf @method('DELETE')
                          <button type="button"
                                  class="btn btn-danger btn-sm rounded-pill px-3 w-100 btn-delete"
                                  data-action="{{ route('admin.sections.destroy', $section) }}"
                                  data-token="{{ csrf_token() }}">
                            🗑️ Hapus
                          </button>
                        </form>
                      @endif
                    </div>
                  </div>
                @endforeach
              @endif
            </div>
          </div>

          {{-- Pagination (jika pakai paginate di controller) --}}
          @if(method_exists($sections,'links'))
            <div class="px-3 px-lg-4 pt-3">
              {{ $sections->appends(['q' => $q, 'department_id' => $deptFilterId ?: null])->links() }}
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
  {{-- DataTables Bootstrap 4 + Responsive CSS (jika belum ada di layout) --}}
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
@endpush

@push('scripts')
  {{-- SweetAlert --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  {{-- DataTables JS (jika belum ada di layout) --}}
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const highlightId = @json(session('highlight_id'));
      const editingId   = @json(request('edit'));

      // Prevent double submit + dirty guard
      const form = document.getElementById('section-form');
      const btn  = document.getElementById('btn-save');
      let isDirty = false;

      if (form && btn) {
        form.addEventListener('submit', function () {
          const spinner = btn.querySelector('.spinner-border');
          const text    = btn.querySelector('.btn-text');
          btn.disabled  = true;
          if (spinner) spinner.classList.remove('d-none');
          if (text)    text.classList.add('opacity-75');
          isDirty = false;
        });
        form.addEventListener('input', () => { isDirty = true; });
      }

      window.addEventListener('beforeunload', function (e) {
        if (!isDirty) return;
        e.preventDefault(); e.returnValue = '';
      });

      document.body.addEventListener('click', function (e) {
        const link = e.target.closest('a');
        if (!link) return;
        if (link.closest('.dataTables_wrapper')) return;
        if (link.target === '_blank') return;
        if (isDirty) {
          e.preventDefault();
          Swal.fire({
            title: 'Perubahan belum disimpan',
            text: 'Tinggalkan halaman ini? Perubahan Anda akan hilang.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Tinggalkan',
            cancelButtonText: 'Kembali'
          }).then(res => { if (res.isConfirmed) { isDirty = false; window.location.href = link.href; } });
        }
      });

      // SweetAlert delete (delegasi)
      document.body.addEventListener('click', function (e) {
        const delBtn = e.target.closest('.btn-delete');
        if (!delBtn) return;
        e.preventDefault();
        Swal.fire({
          title: 'Yakin ingin menghapus?',
          text: 'Data yang dihapus tidak bisa dikembalikan!',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#e3342f',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Ya, hapus!',
          cancelButtonText: 'Batal'
        }).then((result) => {
          if (result.isConfirmed) {
            let formEl = delBtn.closest('form');
            if (formEl) { isDirty = false; formEl.submit(); return; }
            // Fallback (tanpa form)
            const action = delBtn.dataset.action;
            const token  = delBtn.dataset.token || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (action && token) {
              const tmp = document.createElement('form');
              tmp.method = 'POST'; tmp.action = action; tmp.style.display = 'none';
              const inToken  = document.createElement('input'); inToken.name='_token'; inToken.value=token; tmp.appendChild(inToken);
              const inMethod = document.createElement('input'); inMethod.name='_method'; inMethod.value='DELETE'; tmp.appendChild(inMethod);
              document.body.appendChild(tmp);
              isDirty = false; tmp.submit();
            }
          }
        });
      });

      // Highlight helpers
      function runHighlightOnce() {
        if (highlightId) {
          const tr = document.querySelector(`#sections-table tbody tr[data-id="${highlightId}"]`);
          if (tr) { tr.classList.add('table-warning','row-highlight'); tr.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
          const card = document.querySelector(`.d-md-none .list-group-item[data-id="${highlightId}"]`);
          if (card) { card.classList.add('border','border-warning','shadow'); card.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        }
        if (editingId) {
          const trEdit = document.querySelector(`#sections-table tbody tr[data-id="${editingId}"]`);
          if (trEdit) { trEdit.classList.add('table-info','row-highlight'); }
          const cardEdit = document.querySelector(`.d-md-none .list-group-item[data-id="${editingId}"]`);
          if (cardEdit) { cardEdit.classList.add('border','border-info','shadow'); }
        }
      }
      function focusEditForm() {
        const card  = document.getElementById('section-form-card');
        const input = document.getElementById('name');
        if (card) { card.classList.add('form-card-highlight'); card.scrollIntoView({ behavior: 'smooth', block: 'start' }); setTimeout(() => card.classList.remove('form-card-highlight'), 2000); }
        if (input) { try { input.focus(); input.select(); } catch(_){} }
      }

      // DataTables init: DOM tanpa search bawaan (biar ga bingung dgn toolbar di atas)
      const tableEl = document.getElementById('sections-table');
      if (tableEl && window.jQuery) {
        const dt = jQuery(tableEl).DataTable({
          responsive: true,
          pageLength: 10,
          lengthMenu: [10, 25, 50, 100],
          order: [[1, 'asc']],
          columnDefs: [
            { orderable: false, targets: [0, 3, 4] },
            { searchable: false, targets: [0, 4] }
          ],
          dom: "<'row'<'col-12'tr>>" + "<'row align-items-center px-3 pb-2'<'col-sm-6'i><'col-sm-6 text-sm-right'p>>",
          language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json',
            emptyTable: 'Belum ada data seksi'
          }
        });
        dt.on('init.dt draw.dt', runHighlightOnce);
        setTimeout(runHighlightOnce, 0);
        window.addEventListener('resize', () => dt.columns.adjust());
        if (editingId) focusEditForm();
      } else {
        // Non-DataTables
        runHighlightOnce();
        if (editingId) focusEditForm();
      }

      // Auto-dismiss flash
      setTimeout(()=>{ document.querySelectorAll('.alert.alert-success').forEach(a=>a.classList.remove('show')); }, 3500);
    });
  </script>
@endpush
