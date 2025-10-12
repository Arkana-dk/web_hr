{{-- resources/views/admin/pages/employee-deductions-index.blade.php --}}
@extends('layouts.master')

@section('title','Potongan Karyawan')

@section('content')
<div class="container-fluid">
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Daftar Karyawan & Potongan</h6>
    </div>
    <div class="card-body">
      {{-- Filter & Search --}}
      <form method="GET" class="form-inline mb-3">
        <div class="input-group input-group-sm mr-2">
          <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
          </div>
          <input type="text" name="search"
                 class="form-control" 
                 placeholder="Cari nama..." 
                 value="{{ request('search') }}">
        </div>
        <select name="department" class="form-control form-control-sm mr-2">
          <option value="">-- Semua Dept --</option>
          @foreach($departments as $d)
            <option value="{{ $d->id }}" {{ request('department') == $d->id ? 'selected' : '' }}>
              {{ $d->name }}
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
              <th class="text-center">Total Potongan</th>
              <th style="width:20%">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($employees as $idx => $emp)
              @php
                // selalu edit by employee id
                $editRoute = route('admin.employee-deductions.edit', ['employee' => $emp->id]);
              @endphp
              <tr>
                <td>{{ $employees->firstItem() + $idx }}</td>
                <td>{{ $emp->name }}</td>
                <td>{{ optional($emp->department)->name ?? '–' }}</td>
                <td>{{ optional($emp->position)->name ?? '–' }}</td>
                <td class="text-center">
                  Rp {{ number_format($emp->deductions->sum('amount'), 0, ',', '.') }}
                </td>
                <td class="text-nowrap">
                  <!-- Tambah -->
                  <a href="{{ route('admin.employee-deductions.create', ['employee_id' => $emp->id]) }}"
                     class="btn btn-sm btn-primary" title="Tambah Potongan">
                    <i class="fas fa-plus"></i>
                  </a>
                  <!-- Lihat -->
                  <a href="{{ route('admin.employee-deductions.show', $emp->id) }}"
                     class="btn btn-sm btn-info" title="Detail">
                    <i class="fas fa-eye"></i>
                  </a>
                  <!-- Edit -->
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
