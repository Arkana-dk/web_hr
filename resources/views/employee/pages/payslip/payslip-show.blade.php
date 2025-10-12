@extends('layouts.master')
@section('title','Detail Payslip')
@section('content')
<div class="container-fluid">
  <a href="{{ route('employee.payslip.index') }}" class="btn btn-sm btn-secondary mb-3">← Kembali</a>

  <h2>Slip Gaji: {{ $payroll->employee->name }}</h2>
  <p>Periode: {{ $payroll->start_date->format('Y-m') }} – {{ $payroll->end_date->format('Y-m') }}</p>

  <h3>Pendapatan</h3>
  <ul class="list-group mb-4">
    @foreach ($payroll->details
        ->whereIn('component_type',['allowance','overtime'])
        ->where('effective_month',$payroll_month)
      as $detail)
      <li class="list-group-item d-flex justify-content-between">
        {{ ucfirst($detail->component_type) }}: {{ $detail->component_name }}
        <span>Rp {{ number_format($detail->amount, 2, ',', '.') }}</span>
      </li>
    @endforeach
  </ul>

  <h3>Potongan</h3>
  <ul class="list-group mb-4">
    @foreach ($payroll->details
        ->where('component_type','deduction')
        ->where('effective_month',$payroll_month)
      as $detail)
      <li class="list-group-item d-flex justify-content-between">
        {{ $detail->component_name }}
        <span>Rp {{ number_format($detail->amount, 2, ',', '.') }}</span>
      </li>
    @endforeach
  </ul>

  <h3>Total Take Home Pay</h3>
  <p class="h4">
    Rp {{
      number_format(
        $payroll->details
            ->whereIn('component_type',['allowance','overtime'])
            ->where('effective_month',$payroll_month)
            ->sum('amount')
        - $payroll->details
            ->where('component_type','deduction')
            ->where('effective_month',$payroll_month)
            ->sum('amount'),
        2, ',', '.'
      )
    }}
  </p>

  <a href="{{ route('employee.payslip.pdf',$payroll) }}" class="btn btn-sm btn-secondary mt-3">
    Download PDF
  </a>
</div>
@endsection
