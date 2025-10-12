{{-- resources/views/admin/pages/position-show.blade.php --}}
@extends('layouts.master')

@section('title', 'Employees in Position')

@section('content')
<div class="container-fluid px-3 px-md-4 mt-4">

  <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-3">
    <h1 class="h4 mb-2 mb-md-0">Position: {{ $position->name }}</h1>
    <a href="{{ route('admin.positions.index', ['department_id' => $position->department_id]) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
      <i class="fas fa-arrow-left"></i> <span class="d-none d-sm-inline">Back to Positions</span>
    </a>
  </div>

  {{-- Search --}}
  <form method="GET" class="mb-3" id="search-form">
    <div class="form-row align-items-end">
      <div class="col-12 col-sm-8 col-md-6 mb-2">
        <label for="search" class="font-weight-bold">Search</label>
        <div class="input-group">
          <input type="text" name="search" id="search" class="form-control" placeholder="Search name, email, or phone..." value="{{ request('search','') }}">
          <div class="input-group-append">
            <button class="btn btn-primary rounded-pill" type="submit"><i class="fas fa-search"></i></button>
          </div>
        </div>
      </div>
      @if(request('search'))
      <div class="col-12 col-sm-4 col-md-3 mb-2">
        <label class="d-block font-weight-bold invisible">Reset</label>
        <a href="{{ route('admin.positions.show', $position) }}" class="btn btn-outline-secondary btn-block rounded-pill">Reset</a>
      </div>
      @endif
    </div>
  </form>

  <div class="card shadow mb-4">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold">Employees in this Position</h6>
      @if($employees instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <small class="text-white-50">Total: {{ $employees->total() }}</small>
      @endif
    </div>
    <div class="card-body">

      {{-- Desktop / Tablet Table --}}
      <div class="table-responsive-md d-none d-md-block">
        <table class="table table-bordered table-hover mb-0 align-middle" style="min-width:900px">
          <thead class="thead-light">
            <tr>
              <th style="width:5%">#</th>
              <th style="width:10%">Photo</th>
              <th>Name</th>
              <th>Employee Number</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Title</th>
              <th>Department</th>
            </tr>
          </thead>
          <tbody>
            @forelse($employees as $emp)
              <tr>
                <td>{{ $employees->firstItem() + $loop->index }}</td>
                <td>
                  @if($emp->photo)
                    <img src="{{ asset('storage/'.$emp->photo) }}" class="rounded" style="width:75px; height:100px; object-fit:cover;" loading="lazy" alt="{{ $emp->name }}">
                  @else
                    <div class="avatar-placeholder">—</div>
                  @endif
                </td>
                <td>{{ $emp->name }}</td>
                <td><span class="badge badge-light badge-pill">{{ $emp->employee_number }}</span></td>
                <td><a href="mailto:{{ $emp->email }}">{{ $emp->email }}</a></td>
                <td>{{ optional($emp->recruitment)->phone ?? '—' }}</td>
                <td>{{ $emp->title ?? '—' }}</td>
                <td><span class="badge badge-info badge-pill">{{ $emp->department->name }}</span></td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-4">No employees found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Mobile Card List --}}
      <div class="d-md-none">
        <div class="list-group">
          @forelse($employees as $emp)
            <div class="list-group-item">
              <div class="d-flex">
                <div class="mr-3">
                  @if($emp->photo)
                    <img src="{{ asset('storage/'.$emp->photo) }}" class="rounded" style="width:64px; height:84px; object-fit:cover;" loading="lazy" alt="{{ $emp->name }}">
                  @else
                    <div class="avatar-placeholder-sm">—</div>
                  @endif
                </div>
                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <div class="font-weight-bold">{{ $emp->name }}</div>
                      <div class="small text-muted">{{ $emp->title ?? '—' }}</div>
                    </div>
                    <span class="badge badge-info badge-pill">{{ $emp->department->name }}</span>
                  </div>
                  <div class="mt-2 small">
                    <div class="mb-1"><span class="text-muted">No:</span> <span class="badge badge-light badge-pill">{{ $emp->employee_number }}</span></div>
                    <div class="mb-1"><i class="far fa-envelope"></i> <a href="mailto:{{ $emp->email }}">{{ $emp->email }}</a></div>
                    <div class="mb-1"><i class="fas fa-phone"></i> {{ optional($emp->recruitment)->phone ?? '—' }}</div>
                  </div>
                </div>
              </div>
            </div>
          @empty
            <div class="text-center text-muted py-3">No employees found.</div>
          @endforelse
        </div>
      </div>

      {{-- Pagination --}}
      <div class="d-flex justify-content-center mt-3">
        {{ $employees->appends(['search' => request('search')])->links('pagination::bootstrap-4') }}
      </div>

    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  /* Why: keep layout stable even when no photo */
  .avatar-placeholder { width:75px; height:100px; display:flex; align-items:center; justify-content:center; background:#f8f9fa; color:#adb5bd; border-radius:.25rem; }
  .avatar-placeholder-sm { width:64px; height:84px; display:flex; align-items:center; justify-content:center; background:#f8f9fa; color:#adb5bd; border-radius:.25rem; }
  .pagination .page-link { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.addEventListener('DOMContentLoaded', function(){
    // Success toast
    @if(session('success'))
    Swal.fire({ icon: 'success', title: 'Berhasil', text: @json(session('success')), timer: 2200, toast: true, position: 'top-end', showConfirmButton: false });
    @endif

    // Info when search has no results
    @if(request('search') && $employees->count() === 0)
    Swal.fire({ icon: 'info', title: 'Tidak ditemukan', text: 'Tidak ada karyawan yang cocok dengan pencarian Anda.' });
    @endif
  });
</script>
@endpush