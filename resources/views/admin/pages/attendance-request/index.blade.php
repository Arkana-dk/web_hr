{{-- resources/views/admin/pages/attendance-requests.blade.php --}}
@extends('layouts.master')

@section('title','Daftar Pengajuan Presensi')

@section('content')
<div class="container-fluid">
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  <div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Daftar Pengajuan Presensi</h6>

      <form method="GET" class="form-inline">
        <div class="input-group mr-2">
          <input type="text" name="search" class="form-control form-control-sm"
                 placeholder="Cari nama/NIK..." value="{{ request('search','') }}">
          <div class="input-group-append">
            <button class="btn btn-outline-secondary btn-sm" type="submit">
              <i class="fas fa-search"></i>
            </button>
          </div>
        </div>
        <div class="form-group mr-2">
          <select name="status" class="form-control form-control-sm">
            <option value="">-- Semua Status --</option>
            <option value="pending"   {{ request('status')=='pending'   ? 'selected':'' }}>Pending</option>
            <option value="approved"  {{ request('status')=='approved'  ? 'selected':'' }}>Disetujui</option>
            <option value="rejected"  {{ request('status')=='rejected'  ? 'selected':'' }}>Ditolak</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        @if(request()->hasAny(['search','status']))
          <a href="{{ route('admin.attendance-requests.index') }}"
             class="btn btn-secondary btn-sm ml-2">Reset</a>
        @endif
      </form>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered" width="100%">
          <thead class="thead-light">
            <tr>
              <th style="width:5%">No</th>
              <th>Pegawai</th>
              <th>Tanggal</th>
              <th>Jenis</th>
              <th>Alasan</th>
              <th>Status</th>
              <th style="width:20%">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($requests as $req)
              <tr>
                <td>{{ $requests->firstItem() + $loop->index }}</td>
                <td>{{ $req->employee->name }}<br><small>{{ $req->employee->nik }}</small></td>
                <td>{{ \Carbon\Carbon::parse($req->date)->format('d M Y') }}</td>
                <td>{{ ucwords(str_replace('-',' ', $req->type)) }}</td>
                <td>{{ $req->reason ?? '—' }}</td>
                <td>
                  <span class="badge badge-{{ $req->status=='approved'?'success':($req->status=='rejected'?'danger':'warning') }}">
                    {{ ucfirst($req->status) }}
                  </span>
                </td>
                <td>
                  @if($req->status=='pending')
                    <form action="{{ route('admin.attendance-requests.approve', $req->id) }}"
                          method="POST" class="d-inline">
                      @csrf @method('PATCH')
                      <button class="btn btn-success btn-sm">Setujui</button>
                    </form>
                    <form action="{{ route('admin.attendance-requests.reject', $req->id) }}"
                          method="POST" class="d-inline">
                      @csrf @method('PATCH')
                      <button class="btn btn-danger btn-sm">Tolak</button>
                    </form>
                  @else
                    <em>Selesai</em>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center">Tidak ada pengajuan.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div class="d-flex justify-content-center">
        {{ $requests->appends(request()->only(['search','status']))
                     ->links('pagination::bootstrap-4') }}
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
.pagination .page-link {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.querySelectorAll('.btn-sm.btn-danger, .btn-sm.btn-success').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const form = this.closest('form');
      const action = this.textContent.trim().toLowerCase();
      Swal.fire({
        title: `Yakin ingin ${action}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya',
        cancelButtonText: 'Batal'
      }).then(result => {
        if (result.isConfirmed) form.submit();
      });
    });
  });
</script>
@endpush
