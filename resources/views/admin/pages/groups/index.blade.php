{{-- resources/views/admin/groups/index.blade.php --}}
@extends('layouts.master')

@section('title','Data Groups')

@push('styles')
<style>
  /* ===== Hero Card (konsisten dengan Transport/Departments) ===== */
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
  .font-weight-500 { font-weight: 500; }

  /* ===== Avatars (stack) ===== */
  .avatar-stack{ display:flex; align-items:center; }
  .avatar-xs{
    width:30px; height:30px; border-radius:50%; object-fit:cover;
    border:2px solid #fff; box-shadow:0 1px 3px rgba(0,0,0,.1); margin-right:-8px;
  }
  .avatar-more{
    width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    font-size:.75rem; font-weight:700; background:#eef2ff; color:#3730a3; border:2px solid #fff; margin-left:8px;
  }

  /* ===== Buttons ===== */
  .btn-icon{ display:inline-flex; align-items:center; gap:.4rem; }
</style>
@endpush

@section('content')
@php
  $q = request('q');
  $totalGroups = method_exists($groups,'total') ? $groups->total() : $groups->count();
  $rowStart    = method_exists($groups,'firstItem') ? $groups->firstItem() : 1;
@endphp

<div class="container-fluid">

  {{-- ===== Hero Card ===== --}}
  <div class="card hero-card shadow-sm mb-4 position-relative overflow-hidden">
    <div class="shine"></div>
    <div class="card-body py-4 px-4 px-lg-5">
      <div class="d-flex flex-column flex-lg-row align-items-lg-center">
        <div class="flex-grow-1">
          <div class="d-flex align-items-center mb-2">
            <span class="hero-emoji mr-3">👥</span>
            <div>
              <h1 class="h4 mb-1 text-gray-800">Groups</h1>
              <p class="mb-0 text-muted">Kelola grup dan anggota karyawan dengan mudah.</p>
            </div>
          </div>

          <div class="d-flex flex-wrap align-items-center mt-3" style="gap:.5rem 1rem">
            <span class="chip"><i class="fas fa-layer-group"></i> Total: {{ number_format($totalGroups) }}</span>
            @if($q)
              <span class="chip alt"><i class="fas fa-filter"></i> Filter: “{{ $q }}”</span>
            @endif
          </div>
        </div>

        <div class="mt-3 mt-lg-0 ml-lg-4">
          <button class="btn btn-primary btn-icon shadow-sm" data-toggle="collapse" data-target="#addGroup" aria-expanded="false" aria-controls="addGroup">
            <i class="fas fa-plus"></i><span> Tambah Group</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- ===== Toolbar: Search (senada dengan halaman lain) ===== --}}
  <div class="card border-0 mb-3">
    <div class="card-body py-3 px-3 px-lg-4">
      <form method="GET" action="{{ route('admin.groups.index') }}">
        <div class="form-row align-items-center">
          <div class="col-sm-8 col-md-6 mb-2 mb-md-0">
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
              </div>
              <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Cari nama group…">
              @if($q)
                <div class="input-group-append">
                  <a href="{{ route('admin.groups.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
              @endif
            </div>
            <small class="text-muted d-block mt-1">Gunakan kata kunci sebagian atau penuh.</small>
          </div>
          <div class="col-sm-4 col-md-6 text-sm-right">
            <button class="btn btn-outline-primary btn-icon" type="button" data-toggle="collapse" data-target="#addGroup" aria-expanded="false" aria-controls="addGroup">
              <i class="fas fa-plus"></i><span> Tambah Group</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- ===== Add Group (Collapse) ===== --}}
  <div class="collapse mb-4" id="addGroup">
    <div class="card card-body border-0 shadow-sm">
      <form action="{{ route('admin.groups.store') }}" method="POST" class="w-100">
        @csrf
        <div class="form-row">
          <div class="col-12 col-md-8 mb-2 mb-md-0">
            <label class="sr-only" for="group-name">Nama Group</label>
            <input id="group-name" type="text" name="name" class="form-control rounded-pill px-3 @error('name') is-invalid @enderror" placeholder="Nama Group" required minlength="2" maxlength="100">
            @error('name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
          </div>
          <div class="col-12 col-md-4 d-flex">
            <button type="submit" class="btn btn-success rounded-pill px-4 ml-md-2 w-100 w-md-auto">💾 Simpan</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- ===== Data Table (Desktop/Tablet) ===== --}}
  <div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-gray-800">Daftar Group</h6>
      <span class="text-muted small">Menampilkan {{ method_exists($groups,'count') ? $groups->count() : count($groups) }} item</span>
    </div>
    <div class="card-body">
      <div class="table-responsive d-none d-md-block">
        <table class="table table-hover table-bordered align-middle" id="groups-table" width="100%">
          <thead class="thead-light text-center">
            <tr>
              <th style="width:6%">#</th>
              <th class="text-left">Nama Group</th>
              <th style="width:24%">Anggota</th>
              <th style="width:26%">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($groups as $group)
              @php
                $count = $group->employees_count ?? ($group->employees?->count() ?? 0);
                $first = $group->employees ? $group->employees->take(3) : collect();
              @endphp
              <tr data-id="{{ $group->id }}">
                <td class="text-center">{{ $rowStart + $loop->index }}</td>
                <td class="font-weight-500 text-truncate" style="max-width:320px">{{ $group->name }}</td>
                <td>
                  @if($count > 0)
                    <div class="d-flex align-items-center">
                      <div class="avatar-stack mr-2">
                        @foreach($first as $emp)
                          @php
                            $photo = $emp->avatar_url ?? $emp->photo ?? null;
                            if ($photo && !str_starts_with($photo,'http')) { $photo = asset($photo); }
                          @endphp
                          <img class="avatar-xs" src="{{ $photo ?: asset('images/avatar-default.png') }}" alt="">
                        @endforeach
                      </div>
                      <span class="badge badge-info badge-pill">{{ $count }}</span>
                    </div>
                  @else
                    <span class="text-muted small">(Belum ada anggota)</span>
                  @endif
                </td>
                <td class="text-center">
                  <div class="d-flex flex-wrap justify-content-center">
                    <a href="{{ route('admin.groups.show', $group) }}" class="btn btn-info btn-sm rounded-pill px-3 mr-2 mb-2 btn-icon" data-toggle="tooltip" title="Lihat Karyawan">
                      👁️ <span class="d-none d-lg-inline">Lihat</span>
                    </a>
                    <a href="{{ route('admin.groups.edit', $group) }}" class="btn btn-warning btn-sm rounded-pill px-3 mr-2 mb-2 btn-icon" data-toggle="tooltip" title="Edit Group">
                      ✏️ <span class="d-none d-lg-inline">Edit</span>
                    </a>
                    <form action="{{ route('admin.groups.destroy', $group) }}" method="POST" class="d-inline delete-form mb-2">
                      @csrf @method('DELETE')
                      <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 btn-delete btn-icon" data-action="{{ route('admin.groups.destroy', $group) }}" data-token="{{ csrf_token() }}" data-toggle="tooltip" title="Hapus Group">
                        🗑️ <span class="d-none d-lg-inline">Hapus</span>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- ===== Mobile: Card list ===== --}}
      <div class="d-md-none">
        <div class="list-group">
          @forelse($groups as $group)
            @php
              $count = $group->employees_count ?? ($group->employees?->count() ?? 0);
              $first = $group->employees ? $group->employees->take(3) : collect();
            @endphp
            <div class="list-group-item" data-id="{{ $group->id }}">
              <div class="d-flex justify-content-between align-items-start">
                <div class="mr-2">
                  <div class="font-weight-bold">{{ $group->name }}</div>
                  <div class="mt-1 d-flex align-items-center">
                    <div class="avatar-stack mr-2">
                      @foreach($first as $emp)
                        @php
                          $photo = $emp->avatar_url ?? $emp->photo ?? null;
                          if ($photo && !str_starts_with($photo,'http')) { $photo = asset($photo); }
                        @endphp
                        <img class="avatar-xs" src="{{ $photo ?: asset('images/avatar-default.png') }}" alt="">
                      @endforeach
                    </div>
                    <span class="badge badge-info badge-pill">{{ $count }}</span>
                  </div>
                </div>
                <span class="text-secondary">#{{ $rowStart + $loop->index }}</span>
              </div>

              <div class="mt-3 d-flex flex-wrap">
                <a href="{{ route('admin.groups.show', $group) }}" class="btn btn-info btn-sm rounded-pill px-3 mr-2 mb-2 w-100">👁️ Lihat</a>
                <a href="{{ route('admin.groups.edit', $group) }}" class="btn btn-warning btn-sm rounded-pill px-3 mr-2 mb-2 w-100">✏️ Edit</a>
                <form action="{{ route('admin.groups.destroy', $group) }}" method="POST" class="delete-form w-100 mr-2 mb-2">
                  @csrf @method('DELETE')
                  <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 w-100 btn-delete" data-action="{{ route('admin.groups.destroy', $group) }}" data-token="{{ csrf_token() }}">🗑️ Hapus</button>
                </form>
              </div>
            </div>
          @empty
            <div class="text-center text-muted py-4">Belum ada data group</div>
          @endforelse
        </div>
      </div>

      {{-- Pagination (jika pakai paginate di controller) --}}
      @if(method_exists($groups,'links'))
        <div class="px-3 px-lg-4 pt-3">
          {{ $groups->appends(['q' => $q])->links() }}
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
{{-- jQuery + DataTables --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<script>
(function(){
  // Toasts
  @if(session('success'))
  Swal.fire({icon:'success',title:'Berhasil',text:@json(session('success')),timer:2000,toast:true,position:'top-end',showConfirmButton:false});
  @endif
  @if(session('error'))
  Swal.fire({icon:'error',title:'Oops',text:@json(session('error')),timer:2500,toast:true,position:'top-end',showConfirmButton:false});
  @endif

  // DataTables init: dom tanpa search bawaan (kita sudah punya toolbar)
  if (window.jQuery) {
    var $ = window.jQuery;
    var dt = $('#groups-table').DataTable({
      responsive: true,
      pageLength: 10,
      lengthMenu: [10,25,50,100],
      order: [[1,'asc']],
      columnDefs: [
        { orderable:false, targets:[0,3] },
        { searchable:false, targets:[0,2,3] }
      ],
      dom: "<'row'<'col-12'tr>>" + "<'row align-items-center px-3 pb-2'<'col-sm-6'i><'col-sm-6 text-sm-right'p>>",
      language: { url:'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json', emptyTable:'Belum ada data group' }
    });
    $(window).on('resize', function(){ dt.columns.adjust(); });
    $('[data-toggle="tooltip"]').tooltip();
  }

  // Delete confirmation (delegated)
  document.body.addEventListener('click', function(e){
    var delBtn = e.target.closest('.btn-delete');
    if (!delBtn) return;
    e.preventDefault();
    Swal.fire({
      title:'Yakin hapus group ini?',
      text:'Semua data terkait mungkin terpengaruh.',
      icon:'warning',
      showCancelButton:true,
      confirmButtonText:'Ya, hapus',
      cancelButtonText:'Batal',
      confirmButtonColor:'#e3342f',
      cancelButtonColor:'#6c757d'
    }).then(function(result){
      if (result.isConfirmed) {
        var formEl = delBtn.closest('form.delete-form');
        if (formEl) { formEl.submit(); return; }
        // Fallback jika tombol terlepas dari form (responsive)
        var action = delBtn.dataset.action;
        var token  = delBtn.dataset.token || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        if (action && token) {
          var tmp = document.createElement('form');
          tmp.method='POST'; tmp.action=action; tmp.style.display='none';
          var inToken  = document.createElement('input'); inToken.name='_token';  inToken.value=token; tmp.appendChild(inToken);
          var inMethod = document.createElement('input'); inMethod.name='_method'; inMethod.value='DELETE'; tmp.appendChild(inMethod);
          document.body.appendChild(tmp); tmp.submit();
        }
      }
    });
  });
})();
</script>
@endpush
