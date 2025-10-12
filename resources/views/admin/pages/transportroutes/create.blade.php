@extends('layouts.master')

@section('title', 'Tambah Jurusan Transportasi')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 text-gray-800">Tambah Jurusan Transportasi</h1>
    <a href="{{ route('admin.transportroutes.index') }}" class="btn btn-secondary">
      <i class="fas fa-arrow-left"></i> Kembali
    </a>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card shadow">
    <div class="card-body">
      <form action="{{ route('admin.transportroutes.store') }}" method="POST">
        @csrf
        <div class="form-group">
          <label for="route_name">Nama Jurusan</label>
          <input type="text" name="route_name" id="route_name" class="form-control" placeholder="Contoh: Jakarta - Bekasi" value="{{ old('route_name') }}" required>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Simpan
        </button>
      </form>
    </div>
  </div>
</div>
@endsection
