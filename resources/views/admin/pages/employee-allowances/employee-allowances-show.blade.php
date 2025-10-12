@extends('layouts.master')

@section('title', 'Detail Tunjangan - ' . $employee->name)

@section('content')
<div class="container-fluid">
  <div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Detail Tunjangan Karyawan</h6>
      <a href="{{ route('admin.employee-allowances.index') }}" class="btn btn-sm btn-secondary">← Kembali</a>
    </div>

    <div class="card-body">
      <h5 class="mb-3">{{ $employee->name }}</h5>
      <p><strong>Departemen:</strong> {{ $employee->department->name ?? '-' }}</p>
      <p><strong>Posisi:</strong> {{ $employee->position->name ?? '-' }}</p>

      <hr>
      <h6>Daftar Tunjangan</h6>

      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Nama Tunjangan</th>
            <th class="text-end">Jumlah (Rp)</th>
          </tr>
        </thead>
        <tbody>
          @php $total = 0; @endphp
          @forelse($employee->allowances as $a)
            <tr>
              <td>{{ $a->tunjangan->name ?? '-' }}</td>
              <td class="text-end">{{ number_format($a->amount,0,',','.') }}</td>
            </tr>
            @php $total += $a->amount; @endphp
          @empty
            <tr><td colspan="2" class="text-center">Belum ada tunjangan.</td></tr>
          @endforelse
          <tr class="table-secondary">
            <th>Total</th>
            <th class="text-end">{{ number_format($total,0,',','.') }}</th>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
