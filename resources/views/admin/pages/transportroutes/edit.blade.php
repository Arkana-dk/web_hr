@extends('layouts.master')

@section('title', 'Edit Jurusan Transportasi')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 text-gray-800 mb-0">Edit Jurusan Transportasi</h1>
    <a href="{{ route('admin.transportroutes.index') }}" class="btn btn-sm btn-secondary">
      <i class="fas fa-arrow-left"></i> Kembali
    </a>
  </div>

  <div class="card shadow">
    <div class="card-body">
      <form action="{{ route('admin.transportroutes.update', $route->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
          <label for="route_name">Nama Jurusan</label>
          <input type="text" name="route_name" id="route_name" class="form-control @error('route_name') is-invalid @enderror" value="{{ old('route_name', $route->route_name) }}" required>
          @error('route_name')
            <span class="invalid-feedback">{{ $message }}</span>
          @enderror
        </div>

        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Simpan Perubahan
        </button>
      </form>
    </div>
  </div>
</div>
@endsection
