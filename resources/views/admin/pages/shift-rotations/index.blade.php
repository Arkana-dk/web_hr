@extends('layouts.master')

@section('title', 'Rotasi Shift')

@section('content')
<div class="container-fluid">
  <div class="card shadow-sm mb-4">
    <div class="card-body d-flex justify-content-between align-items-center">
      <div>
        <h4 class="mb-1">🔁 Manajemen Rotasi Shift</h4>
        <p class="text-muted mb-0">Kelola urutan rotasi shift per grup kerja.</p>
      </div>
      <a href="{{ route('admin.shift-rotations.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Tambah Rotasi
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success">
      {{ session('success') }}
    </div>
  @endif

  <div class="card shadow-sm">
    <div class="card-body table-responsive">
      <table class="table table-bordered table-striped">
        <thead class="thead-dark">
          <tr>
            <th>#</th>
            <th>Grup</th>
            <th>Urutan</th>
            <th>Shift</th>
            <th>Dibuat</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($shiftRotations as $rotation)
            <tr>
              <td>{{ $loop->iteration }}</td>
              <td>{{ $rotation->group->name }}</td>
              <td>Minggu ke-{{ $rotation->order }}</td>
              <td>{{ $rotation->shift?->name ?? 'Libur' }}</td>
              <td>{{ $rotation->created_at->format('d M Y') }}</td>
              <td>
                <a href="{{ route('admin.shift-rotations.edit', $rotation->id) }}" class="btn btn-sm btn-warning">
                  <i class="fas fa-edit"></i>
                </a>
                <form action="{{ route('admin.shift-rotations.destroy', $rotation->id) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Yakin ingin menghapus rotasi ini?')">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-sm btn-danger">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center">Belum ada rotasi shift.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
