{{-- resources/views/admin/pages/shifts.blade.php --}}
@extends('layouts.master')

@section('title', 'Manajemen Shift')

@section('content')
<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Manajemen Shift</h1>

  <div class="card mb-4">
    <div class="card-header">Tambah Shift</div>
    <div class="card-body">
      <form action="{{ route('admin.shifts.store') }}" method="POST" class="form-inline">
        @csrf
        <div class="form-group mr-2">
          <input name="name" class="form-control" placeholder="Nama Shift" required>
        </div>
        <div class="form-group mr-2">
          <input name="start_time" type="time" class="form-control" required>
        </div>
        <div class="form-group mr-2">
          <input name="end_time" type="time" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Tambah</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">Daftar Shift</div>
    <div class="card-body p-0">
      <table class="table mb-0">
        <thead>
          <tr>
            <th>No</th>
            <th>Nama</th>
            <th>Jam</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($shifts as $s)
            <tr>
              <td>{{ $loop->iteration }}</td>
              <td>{{ $s->name }}</td>
              <td>{{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }}
                  &ndash;
                  {{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }}
              </td>
              <td>
                <form action="{{ route('admin.shifts.destroy', $s) }}" method="POST" class="d-inline delete-form">
                  @csrf
                  @method('DELETE')
                  <button type="button" class="btn btn-sm btn-danger btn-delete">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center">Belum ada data shift.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
  {{-- SweetAlert2 --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          const form = this.closest('.delete-form');

          Swal.fire({
            title: 'Yakin ingin menghapus shift ini?',
            text: 'Data yang dihapus tidak bisa dikembalikan!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
          }).then((result) => {
            if (result.isConfirmed) {
              form.submit();
            }
          });
        });
      });
    });
  </script>
@endpush
