@extends('layouts.master')

@section('title', 'Detail Group')

@section('content')
<div class="container-fluid">

  {{-- Info Statistik Group --}}
  <div class="row mb-4">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card card-kpi border-left-info shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                Group: {{ $group->name }}
              </div>
              <div class="h4 mb-0 font-weight-bold text-gray-800">
                {{ $group->employees->count() }} Pegawai
              </div>
            </div>
            <div class="col-auto">
              <i class="fas fa-users fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Tabel Tambah Employee --}}
  <div class="card shadow mb-4">
    <div class="card-header bg-primary text-white">
      <h6 class="m-0 font-weight-bold">Tambah Employee ke Group</h6>
    </div>
    <div class="card-body">
      <form method="GET" action="{{ route('admin.groups.show', $group) }}" class="mb-3">
        <div class="row">
          <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan Nama atau NIK" value="{{ request('search') }}">
          </div>
          <div class="col-md-2">
            <button class="btn btn-info" type="submit">
              <i class="fas fa-search"></i> Cari
            </button>
          </div>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover">
          <thead class="thead-light">
            <tr>
              <th>#</th>
              <th>Employee Number</th>
              <th>Name</th>
              <th>Departemen</th>
              <th>Posisi</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($availableEmployees as $emp)
              <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $emp->employee_number }}</td>
                <td>{{ $emp->name }}</td>
                <td>{{ $emp->department->name ?? '-' }}</td>
                <td>{{ $emp->position->name ?? '-' }}</td>
                <td>
                  <form action="{{ route('admin.groups.addEmployee', $group) }}" method="POST">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $emp->id }}">
                    <button type="submit" class="btn btn-success btn-sm">
                      <i class="fas fa-plus"></i> Tambah
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted">Masukkan kata kunci untuk menampilkan employee.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Tabel Member Group --}}
  <div class="card shadow mb-4">
    <div class="card-header bg-secondary text-white">
      <h6 class="m-0 font-weight-bold">Employee dalam Group</h6>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover">
          <thead class="thead-light">
            <tr>
              <th>#</th>
              <th>Employee Number</th>
              <th>Nama</th>
              <th>Departemen</th>
              <th>Posisi</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($group->employees as $emp)
              <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $emp->employee_number }}</td>
                <td>{{ $emp->name }}</td>
                <td>{{ $emp->department->name ?? '-' }}</td>
                <td>{{ $emp->position->name ?? '-' }}</td>
                <td>
                  <form action="{{ route('admin.groups.employees.remove', [$group, $emp]) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus employee dari group ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">
                      <i class="fas fa-trash"></i> Hapus
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted">Belum ada employee dalam group ini.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <a href="{{ route('admin.groups.index') }}" class="btn btn-secondary mt-3">← Kembali</a>
    </div>
  </div>
</div>
@endsection
