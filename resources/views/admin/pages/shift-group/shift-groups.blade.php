@extends('layouts.master')

@section('title', 'Manajemen Group Shift')

@section('content')
<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Manajemen Group Shift</h1>

  <div class="mb-3">
    <a href="{{ route('admin.shift-groups.create') }}" class="btn btn-primary">
      <i class="fas fa-plus"></i> Buat Group Baru
    </a>
  </div>

  <div class="card">
    <div class="card-header">Daftar Group Shift</div>
    <div class="card-body p-0">
      <table class="table mb-0 text-nowrap">
        <thead class="thead-light">
          <tr>
            <th>No</th>
            <th>Nama Group</th>
            <th>Shift</th>
            <th>Jumlah Anggota</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($groups as $g)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $g->name }}</td>
            <td>
              @if($g->shift)
                {{ $g->shift->name }}<br>
                <small class="text-muted">
                  {{ $g->shift->start_time }} – {{ $g->shift->end_time }}
                </small>
              @else
                <span class="text-muted">–</span>
              @endif
            </td>
            <td>{{ $g->employees->count() }}</td>
            <td>
              {{-- Lihat detail --}}
              <a href="{{ route('admin.shift-groups.show', $g) }}"
                 class="btn btn-sm btn-info">
                <i class="fas fa-eye"></i>
              </a>

              {{-- Tambah anggota --}}
              <a href="{{ route('admin.shift-groups.select', $g) }}"
                 class="btn btn-sm btn-success">
                <i class="fas fa-user-plus"></i>
              </a>

              {{-- Edit group --}}
              <a href="{{ route('admin.shift-groups.edit', $g) }}"
                 class="btn btn-sm btn-warning">
                <i class="fas fa-edit"></i>
              </a>

              {{-- Hapus group --}}
              <form action="{{ route('admin.shift-groups.destroy', $g) }}"
                    method="POST"
                    class="d-inline"
                    onsubmit="return confirm('Hapus group {{ $g->name }}?');">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-danger">
                  <i class="fas fa-trash"></i>
                </button>
              </form>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="5" class="text-center">Belum ada data group.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
