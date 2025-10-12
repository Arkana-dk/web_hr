@extends('layouts.master')
@section('title','Payslips Anda')
@section('content')
<div class="container-fluid">
  <h4 class="mb-3">Daftar Payslip</h4>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>No</th><th>Periode</th><th>Gaji Pokok</th>
        <th>Tunjangan</th><th>Potongan</th><th>Lembur</th>
        <th>Net Salary</th><th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @foreach($payrolls as $i=>$p)
      <tr>
        <td>{{ $i+1 }}</td>
        <td>{{ $p->start_date->format('Y-m') }}</td>
        <td>Rp {{ number_format($p->basic_salary,0,',','.') }}</td>
        <td>Rp {{ number_format($p->total_allowances,0,',','.') }}</td>
        <td>Rp {{ number_format($p->total_deductions,0,',','.') }}</td>
        <td>Rp {{ number_format($p->overtime_amount,0,',','.') }}</td>
        <td>Rp {{ number_format($p->net_salary,0,',','.') }}</td>
        <td>
          <a href="{{ route('employee.payslip.show',$p) }}" class="btn btn-sm btn-info">Lihat</a>
          <a href="{{ route('employee.payslip.pdf',$p) }}" class="btn btn-sm btn-secondary">PDF</a>
        </td>
      </tr>
      @endforeach
      @if($payrolls->isEmpty())
      <tr><td colspan="8" class="text-center">Belum ada payslip approved.</td></tr>
      @endif
    </tbody>
  </table>
</div>
@endsection
