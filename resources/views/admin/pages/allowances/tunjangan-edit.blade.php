@extends('layouts.master')

@section('title', 'Edit Tunjangan')

@section('content')
<div class="container-fluid">
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
          <h6 class="m-0 font-weight-bold text-primary">Form Edit Tunjangan</h6>
          <a href="{{ route('admin.tunjangan.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
          </a>
        </div>
        <div class="card-body">
          @if ($errors->any())
            <div class="alert alert-danger">
              <ul>
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form action="{{ route('admin.tunjangan.update', $tunjangan->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
              <label for="name" class="form-label">Nama Tunjangan</label>
              <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $tunjangan->name) }}" required>
            </div>

            <div class="mb-3">
              <label for="amount" class="form-label">Jumlah (Rp)</label>
              <input type="number" step="0.01" name="amount" id="amount" class="form-control" value="{{ old('amount', $tunjangan->amount) }}" required>
            </div>

            <div class="text-end">
              <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Update
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
