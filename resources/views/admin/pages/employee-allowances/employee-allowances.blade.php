{{-- resources/views/admin/pages/employee-allowances.blade.php --}}
@extends('layouts.master')

@section('title','Tunjangan Karyawan')

@section('content')
<div class="container-fluid">

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Daftar Karyawan & Tunjangan</h6>
    </div>
    <div class="card-body">
      {{-- Filter & Search --}}
      <form method="GET" class="form-inline mb-3">
        <input type="text" name="search" class="form-control form-control-sm mr-2"
               placeholder="Cari nama..." value="{{ request('search') }}">
        <select name="department" class="form-control form-control-sm mr-2">
          <option value="">-- Semua Dept --</option>
          @foreach($departments as $dept)
            <option value="{{ $dept->id }}"
                    {{ request('department') == $dept->id ? 'selected' : '' }}>
              {{ $dept->name }}
            </option>
          @endforeach
        </select>
        <button class="btn btn-outline-primary btn-sm">Filter</button>
      </form>

      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead class="thead-light">
            <tr>
              <th style="width:5%">No</th>
              <th>Nama</th>
              <th>Departemen</th>
              <th>Posisi</th>
              <th class="text-center">Total Tunjangan</th>
              <th style="width:20%">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($employees as $idx => $emp)
              @php
                // selalu edit allowances milik karyawan ini
                $editRoute = route('admin.employee-allowances.edit', ['employee' => $emp->id]);
              @endphp
              <tr>
                <td>{{ $employees->firstItem() + $idx }}</td>
                <td>{{ $emp->name }}</td>
                <td>{{ optional($emp->department)->name ?? '–' }}</td>
                <td>{{ optional($emp->position)->name ?? '–' }}</td>
                <td class="text-center">
                  Rp {{ number_format($emp->allowances->sum('amount'),0,',','.') }}
                </td>
                <td class="text-nowrap">
                  <!-- Tambah -->
                  <a href="{{ route('admin.employee-allowances.create', ['employee_id' => $emp->id]) }}"
                     class="btn btn-sm btn-primary" title="Tambah">
                    <i class="fas fa-plus"></i>
                  </a>
                  <!-- Lihat -->
                  <a href="{{ route('admin.employee-allowances.show', $emp->id) }}"
                     class="btn btn-sm btn-info" title="Lihat">
                    <i class="fas fa-eye"></i>
                  </a>
                  <!-- Edit multi-allowance untuk karyawan ini -->
                  <a href="{{ $editRoute }}"
                     class="btn btn-sm btn-warning" title="Edit">
                    <i class="fas fa-edit"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center">Belum ada karyawan.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3">
        {{ $employees->links('pagination::bootstrap-4') }}
      </div>
    </div>
  </div>
</div>
@endsection
