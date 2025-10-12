@extends('layouts.master')
@section('title','Tambah Jenis Cuti')
@include('components.leave.styles-soft')


@section('content')
<div class="container-fluid">
  <div class="card hero-card mb-4"><div class="hero-body d-flex justify-content-between align-items-center">
    <div><div class="h3 fw-bold mb-1">Tambah Jenis Cuti</div><div class="opacity-75">Definisikan kategori cuti</div></div>
    <a href="{{ route('admin.leave-types.index') }}" class="btn btn-light btn-pill btn-elev"><i class="fas fa-arrow-left"></i> Kembali</a>
  </div></div>

  <div class="card soft"><div class="card-body">
    <form method="POST" action="{{ route('admin.leave-types.store') }}">
      @include('admin.pages.leave-types._form')
    </form>
  </div></div>
</div>
@endsection
