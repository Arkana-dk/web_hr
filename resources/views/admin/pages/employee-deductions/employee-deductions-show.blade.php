@extends('layouts.master')

@section('title', 'Detail Potongan - ' . $employee->name)

@section('content')
<div class="container-fluid">
  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Detail Potongan Karyawan</h6>
      <a href="{{ route('admin.employee-deductions.index') }}" class="btn btn-sm btn-secondary">← Kembali</a>
    </div>
    <div class="card-body">
      <h5 class="mb-3">{{ $employee->name }}</h5>
      <p><strong>Departemen:</strong> {{ $employee->department->name ?? '-' }}</p>
      <p><strong>Posisi:</strong> {{ $employee->position->name ?? '-' }}</p>
      <hr>
      <h6>Daftar Potongan</h6>
      <ul class="list-group">
        @php $total=0; @endphp
        @forelse($employee->deductions as $d)
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>{{ $d->potongan->name }}</div>
            <div>Rp {{ number_format($d->amount,0,',','.') }}</div>
          </li>
          @php $total+=$d->amount; @endphp
        @empty
          <li class="list-group-item text-muted">Belum ada potongan.</li>
        @endforelse
        <li class="list-group-item d-flex justify-content-between font-weight-bold">
          <span>Total</span>
          <span>Rp {{ number_format($total,0,',','.') }}</span>
        </li>
      </ul>
      <div class="mt-4">
        <a href="{{ route('admin.employee-deductions.create',['employee_id'=>$employee->id]) }}"
           class="btn btn-primary btn-sm">+ Tambah Potongan</a>
      </div>
    </div>
  </div>
</div>
@endsection
