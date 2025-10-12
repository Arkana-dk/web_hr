@extends('layouts.master')
@section('title','Leave Reports')
@include('components.leave.styles-soft')



@section('content')
<div class="container-fluid">
  <div class="card hero-card mb-4"><div class="hero-body d-flex justify-content-between align-items-center">
    <div><div class="h3 fw-bold mb-1">Leave Reports</div><div class="opacity-75">Analitik cuti per tahun</div></div>
    <form method="GET" class="d-flex flex-wrap gap-2">
      <input type="number" name="year" class="form-control form-control-sm" value="{{ $year }}" style="max-width:120px">
      <select name="type" class="form-select form-select-sm" style="min-width:160px">
        <option value="">Semua Tipe</option>
        @foreach($leaveTypes as $lt)<option value="{{ $lt->id }}" @selected(request('type')==$lt->id)>{{ $lt->name }}</option>@endforeach
      </select>
      @if($departments->count())
      <select name="department" class="form-select form-select-sm" style="min-width:180px">
        <option value="">Semua Dept</option>
        @foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department')==$d->id)>{{ $d->name }}</option>@endforeach
      </select>
      @endif
      <button class="btn btn-light btn-sm btn-pill">Terapkan</button>
    </form>
  </div></div>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card soft"><div class="card-header">Komposisi Jenis Cuti</div>
        <div class="card-body"><canvas id="chartType" height="220"></canvas></div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card soft"><div class="card-header">Cuti per Departemen</div>
        <div class="card-body"><canvas id="chartDept" height="220"></canvas></div>
      </div>
    </div>
  </div>

  <div class="card soft mt-3">
    <div class="card-header">Ringkasan per Karyawan</div>
    <div class="table-responsive">
      <table class="table table-hover align-middle table-soft mb-0">
        <thead><tr><th>Nama</th><th>Departemen</th><th>Total Hari</th></tr></thead>
        <tbody>
          @forelse($summary as $row)
          <tr><td class="fw-semibold">{{ $row['employee'] ?? '—' }}</td><td>{{ $row['department'] ?? '—' }}</td><td>{{ $row['days'] ?? 0 }}</td></tr>
          @empty
          <tr><td colspan="3" class="text-center text-muted">Tidak ada data</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const typeCtx = document.getElementById('chartType');
new Chart(typeCtx, {
  type: 'pie',
  data: {
    labels: {!! json_encode($byTypeLabels) !!},
    datasets: [{ data: {!! json_encode($byTypeValues->values()) !!} }]
  }
});

const deptCtx = document.getElementById('chartDept');
new Chart(deptCtx, {
  type: 'bar',
  data: {
    labels: {!! json_encode($byDeptLabels->values()) !!},
    datasets: [{ label: 'Total Hari', data: {!! json_encode($byDeptValues->values()) !!} }]
  },
  options: { scales: { y: { beginAtZero: true } } }
});
</script>
@endpush
