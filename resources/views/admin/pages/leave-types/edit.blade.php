@extends('layouts.master')
@section('title','Edit Jenis Cuti')
@include('components.leave.styles-soft')

@section('content')
<div class="container-fluid">
  <div class="card hero-card mb-4"><div class="hero-body d-flex justify-content-between align-items-center">
    <div><div class="h3 fw-bold mb-1">Edit Jenis Cuti</div><div class="opacity-75">{{ $type->name }}</div></div>
    <a href="{{ route('admin.leave-types.index') }}" class="btn btn-light btn-pill btn-elev"><i class="fas fa-arrow-left"></i> Kembali</a>
  </div></div>

  <div class="card soft"><div class="card-body">
    <form method="POST" action="{{ route('admin.leave-types.update',$type) }}">
      @method('PUT')
      @include('admin.pages.leave-types._form', ['type'=>$type])
    </form>
  </div></div>
</div>
@endsection
