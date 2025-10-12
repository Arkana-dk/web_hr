@extends('layouts.master')
@section('title','Leave Ledger')
@include('components.leave.styles-soft')


@section('content')
<div class="container-fluid">

  {{-- HERO --}}
  <div class="card hero-card mb-3">
    <div class="hero-body d-flex justify-content-between align-items-center">
      <div>
        <div class="h3 fw-bold mb-1">Leave Ledger</div>
        <div class="opacity-75">Audit trail transaksi cuti</div>
      </div>
    </div>
  </div>

  {{-- FILTERS (di bawah hero card) --}}
  <div class="card soft mb-3">
    <div class="card-header">Filter</div>
    <div class="card-body">
      <form method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-md-3">
          <label class="form-label mb-1">Mulai</label>
          <input type="date" name="start" class="form-control" value="{{ request('start') }}">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label mb-1">Selesai</label>
          <input type="date" name="end" class="form-control" value="{{ request('end') }}">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label mb-1">Tipe Cuti</label>
          <select name="type" class="form-select">
            <option value="">Semua Tipe</option>
            @foreach($leaveTypes as $lt)
              <option value="{{ $lt->id }}" @selected(request('type')==$lt->id)>{{ $lt->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label mb-1">Karyawan</label>
          <select name="employee" class="form-select">
            <option value="">Semua Karyawan</option>
            @foreach($employees as $e)
              <option value="{{ $e->id }}" @selected(request('employee')==$e->id)>{{ $e->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label mb-1">Transaksi</label>
          <select name="direction" class="form-select">
            <option value="">Semua</option>
            <option value="debit"  @selected(request('direction')=='debit')>Debit</option>
            <option value="credit" @selected(request('direction')=='credit')>Kredit</option>
          </select>
        </div>

        <div class="col-12 d-flex gap-2 mt-2">
          <button class="btn btn-primary btn-pill"><i class="fas fa-filter"></i> Terapkan</button>
          <a href="{{ route('admin.leave-ledger.index') }}" class="btn btn-light btn-pill"><i class="fas fa-undo"></i> Reset</a>
        </div>
      </form>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card soft">
    <div class="table-responsive">
      <table class="table table-hover align-middle table-soft mb-0">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Karyawan</th>
            <th>Dept</th>
            <th>Tipe</th>
            <th>Transaksi</th>
            <th class="text-end">Jumlah</th>
            <th>Keterangan</th>
          </tr>
        </thead>
        <tbody>
          @forelse($ledgers as $lg)
            @php $amount = (float) ($lg->quantity ?? 0); @endphp
<tr>
  <td>{{ optional($lg->entry_date)->format('d M Y H:i') ?? '—' }}</td>
  <td class="fw-semibold">{{ $lg->employee?->name ?? '—' }}</td>
  <td>{{ $lg->employee?->department?->name ?? '—' }}</td>
  <td>{{ $lg->leaveType?->name ?? '—' }}</td>
  <td>
    {!! ($lg->direction === 'debit')
        ? '<span class="badge bg-danger-subtle text-danger border">Debit</span>'
        : '<span class="badge bg-success-subtle text-success border">Kredit</span>' !!}
  </td>
  <td class="text-end">{{ rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.') }}</td>
  <td>{{ $lg->note ?? '—' }}</td>
</tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada transaksi</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-body">
      {{ $ledgers->links() }}
    </div>
  </div>

</div>
@endsection
