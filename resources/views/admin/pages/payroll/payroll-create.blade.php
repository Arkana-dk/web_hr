@extends('layouts.master')
@section('title','Buat Payroll')
@section('content')
<div class="container-fluid">
  <div class="card shadow mb-4">
    <div class="card-header">
      <h6>Buat Payroll Baru</h6>
    </div>
    <div class="card-body">
      <form action="{{ route('admin.payroll.store') }}" method="POST">
        @csrf
        <div class="row">
          <div class="col-md-4 mb-3">
            <label>Periode</label>
            <input type="month" name="period" class="form-control" required>
          </div>
          <div class="col-md-4 mb-3">
            <label>Tgl Mulai</label>
            <input type="date" name="start_date" class="form-control" required>
          </div>
          <div class="col-md-4 mb-3">
            <label>Tgl Selesai</label>
            <input type="date" name="end_date" class="form-control" required>
          </div>
        </div>
        <button class="btn btn-primary">Proses Payroll</button>
      </form>
    </div>
  </div>
</div>
@endsection
