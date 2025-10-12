@extends('layouts.master')
@section('title','Detail Payroll')
@section('content')
<div class="container-fluid">
  <a href="{{ route('admin.payroll.index') }}" class="btn btn-sm btn-secondary mb-3">← Kembali</a>
  <div class="card shadow">
    <div class="card-body">
      <h5>
        {{ $payroll->employee->name }}
        —
        {{ $payroll->period ?? \Carbon\Carbon::parse($payroll->start_date)->format('Y-m') }}
      </h5>

      <p>Gaji Pokok: <strong>Rp {{ number_format($payroll->basic_salary,0,',','.') }}</strong></p>
      <p>Total Tunjangan: <strong>Rp {{ number_format($payroll->total_allowances,0,',','.') }}</strong></p>
      <p>Total Potongan: <strong>Rp {{ number_format($payroll->total_deductions,0,',','.') }}</strong></p>
      <p>Total Lembur: <strong>Rp {{ number_format($payroll->overtime_amount,0,',','.') }}</strong></p>

      <hr>

      <h6>Detail Komponen</h6>
      <ul class="list-group mb-3">
        @foreach($payroll->details as $d)
        <li class="list-group-item d-flex justify-content-between">
          {{ ucfirst($d->component_type) }} — {{ $d->component_name }}
          <span>Rp {{ number_format($d->amount,0,',','.') }}</span>
        </li>
        @endforeach
      </ul>

      <h5>Net Salary: <strong>Rp {{ number_format($payroll->net_salary,0,',','.') }}</strong></h5>
    </div>
  </div>
</div>
@endsection
