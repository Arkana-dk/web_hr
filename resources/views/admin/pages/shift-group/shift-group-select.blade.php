{{-- resources/views/admin/pages/shift-group-select.blade.php --}}
@extends('layouts.master')

@section('title', 'Pilih Anggota untuk Group Shift: ' . $group->name)

@section('content')
<div class="container-fluid">
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <h1 class="h3 mb-4 text-gray-800">
    Pilih Anggota untuk <strong>{{ $group->name }}</strong>
  </h1>

  <div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span class="font-weight-bold text-primary">Daftar Pegawai</span>
      <form method="GET" class="form-inline">
        <div class="input-group input-group-sm">
          <input type="text" name="search"
                 class="form-control"
                 placeholder="Cari nama, NIK, atau jabatan..."
                 value="{{ request('search') }}">
          <div class="input-group-append">
            <button class="btn btn-outline-secondary" type="submit">
              <i class="fas fa-search"></i>
            </button>
          </div>
        </div>
      </form>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered mb-0" width="100%" cellspacing="0">
          <thead class="thead-light">
            <tr>
              <th>No</th>
              <th>Photo</th>
              <th>Nama</th>
              <th>Department</th>
              <th>Title</th>
              <th>Position</th>
              <th>Phone</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($employees as $e)
              <tr>
                <td>{{ $employees->firstItem() + $loop->index }}</td>
                <td class="text-center">
                  @if($e->photo)
                    <img src="{{ asset('storage/'.$e->photo) }}"
                         alt="Foto {{ $e->name }}"
                         style="width:60px; height:80px; object-fit:cover; border-radius:4px;">
                  @else
                    <div style="width:60px; height:80px; background:#f0f0f0; border-radius:4px;"></div>
                  @endif
                </td>
                <td>{{ $e->name }}</td>
                <td>{{ optional($e->department)->name ?? '—' }}</td>
                <td>{{ $e->title ?? '—' }}</td>
                <td>{{ optional($e->position)->name ?? '—' }}</td>
                <td>{{ $e->phone ?? '—' }}</td>
                <td class="text-center">
                  <form action="{{ route('admin.shift-groups.attach-employee', [
                      'shiftGroup' => $group->id,
                      'employee'   => $e->id
                    ]) }}"
                    method="POST"
                    class="d-inline attach-form">
                    @csrf
                    <button type="button" class="btn btn-sm btn-primary btn-attach">
                      <i class="fas fa-plus-circle"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center">Tidak ada data pegawai.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-center mt-3">
        {{ $employees->appends(request()->only('search'))->links('pagination::bootstrap-4') }}
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
  document.querySelectorAll('.btn-attach').forEach(btn => {
    btn.addEventListener('click', function() {
      const form = this.closest('form.attach-form');
      Swal.fire({
        title: 'Tambah pegawai?',
        text: 'Anda akan menambahkan pegawai ini ke group {{ $group->name }}.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, tambahkan',
        cancelButtonText: 'Batal'
      }).then(result => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
</script>
@endpush
