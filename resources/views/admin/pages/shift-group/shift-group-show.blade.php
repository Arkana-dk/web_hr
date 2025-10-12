@extends('layouts.master')

@section('title', 'Detail Group Shift')

@section('content')
<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Detail Group Shift</h1>

  {{-- Info group & shift --}}
  <div class="card mb-4">
    <div class="card-body">
      <h5>Nama Group: {{ $group->name }}</h5>
      <h6>Shift:</h6>
      @if($group->shift)
        <p>
          <strong>{{ $group->shift->name }}</strong><br>
          {{ $group->shift->start_time }} – {{ $group->shift->end_time }}
        </p>
      @else
        <p class="text-muted">Belum diset</p>
      @endif
    </div>
  </div>

  {{-- Tombol tambah anggota --}}
  <div class="mb-3">
    <a href="{{ route('admin.shift-groups.select', $group) }}"
       class="btn btn-success">
      <i class="fas fa-user-plus"></i> Tambah Anggota
    </a>
  </div>

  {{-- Daftar anggota --}}
  <div class="card">
    <div class="card-header">Daftar Anggota</div>
    <div class="card-body p-0">
      <table class="table mb-0">
        <thead>
          <tr>
            <th>No</th>
            <th>Nama</th>
            <th>Department</th>
            <th>Position</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($employees as $emp)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $emp->name }}</td>
            <td>{{ $emp->department->name }}</td>
            <td>{{ $emp->position->name }}</td>
            <td>
              <form action="{{ route('admin.shift-groups.detach', [$group, $emp]) }}"
                    method="POST"
                    class="d-inline"
                    onsubmit="return confirm('Hapus {{ $emp->name }} dari group?');">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-danger">Hapus</button>
              </form>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="5" class="text-center">Belum ada anggota.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer">
      {{ $employees->links() }}
    </div>
  </div>
</div>
@endsection
