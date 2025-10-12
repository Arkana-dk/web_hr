@extends('layouts.master')
@section('title','Leave Requests')
@include('components.leave.styles-soft')


@section('content')
<div class="container-fluid"> 

  {{-- HERO --}}
  <div class="card hero-card mb-4">
    <div class="hero-body d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between">
      <div>
        <div class="h3 mb-1 fw-bold">Leave Requests</div>
        <div class="opacity-75">Kelola pengajuan cuti: monitor & proses approval</div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.leave-requests.index', array_merge(request()->all(), ['status'=>'pending'])) }}" class="btn btn-soft-white btn-pill btn-elev">
          <i class="fas fa-hourglass-half"></i> Pending
        </a>
        <a href="{{ route('admin.leave-requests.index', array_merge(request()->all(), ['status'=>'approved'])) }}" class="btn btn-light btn-pill btn-elev">
          <i class="fas fa-check-circle"></i> Approved
        </a>
        <a href="{{ route('admin.leave-requests.index', array_merge(request()->all(), ['status'=>'rejected'])) }}" class="btn btn-light btn-pill btn-elev">
          <i class="fas fa-times-circle"></i> Rejected
        </a>
      </div>
    </div>
  </div>

  {{-- STRIP STAT --}}
  <div class="row g-3 mb-4">
    <div class="col-12 col-md-4"><div class="card stat-card h-100"><div class="stat-body">
      <div><div class="stat-label">Pending</div><div class="stat-value fs-5">{{ $stats['pending'] ?? 0 }}</div></div>
      <i class="fas fa-hourglass-half text-secondary fs-4"></i>
    </div></div></div>
    <div class="col-12 col-md-4"><div class="card stat-card h-100"><div class="stat-body">
      <div><div class="stat-label">Approved (bulan ini)</div><div class="stat-value fs-5">{{ $stats['approved_this_month'] ?? 0 }}</div></div>
      <i class="fas fa-check text-secondary fs-4"></i>
    </div></div></div>
    <div class="col-12 col-md-4"><div class="card stat-card h-100"><div class="stat-body">
      <div><div class="stat-label">Rata-rata sisa kuota</div><div class="stat-value fs-5">{{ $stats['avg_remaining'] ?? '—' }}</div></div>
      <i class="fas fa-chart-line text-secondary fs-4"></i>
    </div></div></div>
  </div>

  {{-- FILTER + TABEL --}}
  <div class="card soft">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
      <span>Daftar Pengajuan</span>
      <form method="GET" class="d-flex flex-wrap gap-2">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Cari nama/alasannya…" />
        <select name="type" class="form-select form-select-sm" style="min-width:180px">
          <option value="">Semua Tipe</option>
          @foreach($leaveTypes as $lt)
            <option value="{{ $lt->id }}" @selected(request('type')==$lt->id)>{{ $lt->name }}</option>
          @endforeach
        </select>
        <select name="status" class="form-select form-select-sm" style="min-width:160px">
          <option value="">Semua Status</option>
          @foreach(['pending','approved','rejected','cancelled'] as $st)
            <option value="{{ $st }}" @selected(request('status')==$st)>{{ ucfirst($st) }}</option>
          @endforeach
        </select>
        <input type="date" name="start" value="{{ request('start') }}" class="form-control form-control-sm">
        <input type="date" name="end" value="{{ request('end') }}" class="form-control form-control-sm">
        <button class="btn btn-primary btn-sm btn-pill"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route('admin.leave-requests.index') }}" class="btn btn-light btn-sm btn-pill">Reset</a>
      </form>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle table-soft mb-0">
       <thead>
  <tr>
    <th>Pengaju</th>
    <th>Departemen</th>
    <th>Tipe</th>
    <th>Periode</th>
    <th>Hari</th>
    <th>Status</th>
    <th>Approved By</th> {{-- ✅ Tambahan --}}
    <th class="text-end">Aksi</th>
  </tr>
</thead>

<tbody>
  @forelse($leaveRequests as $lr)
  <tr>
    <td>
      <div class="fw-semibold">{{ $lr->employee?->name ?? '-' }}</div>
      <div class="text-muted small">{{ $lr->employee?->employee_number ?? '' }}</div>
    </td>
    <td>{{ $lr->employee?->department?->name ?? '—' }}</td>
    <td><span class="badge badge-soft">{{ $lr->type?->name ?? '—' }}</span></td>
    <td>{{ optional($lr->start_date)->format('d M Y') }} – {{ optional($lr->end_date)->format('d M Y') }}</td>
    <td>{{ rtrim(rtrim(number_format($lr->days ?? 0, 2, ',', '.'), '0'), ',') }}</td>
    <td>@include('components.leave.status-badge',['status'=>$lr->status])</td>

    {{-- ✅ Kolom baru: reviewer --}}
    <td>
      @if($lr->status === 'approved')
        <div class="fw-semibold text-success">
          {{ $lr->reviewer?->name ?? '—' }}
        </div>
        <div class="small text-muted">
          {{ optional($lr->updated_at)->format('d M Y H:i') }}
        </div>
      @elseif($lr->status === 'rejected')
        <div class="fw-semibold text-danger">
          {{ $lr->reviewer?->name ?? '—' }}
        </div>
        <div class="small text-muted">
          {{ optional($lr->updated_at)->format('d M Y H:i') }}
        </div>
      @else
        <span class="text-muted small">—</span>
      @endif
    </td>

    <td class="text-end">
      {{-- aksi approve/reject tetap sama --}}
      <a href="{{ route('admin.leave-requests.show',$lr) }}" class="btn btn-sm btn-light btn-pill">
        <i class="fas fa-eye"></i> Detail
      </a>
      @if($lr->status === 'pending')
        <form action="{{ route('admin.leave-requests.approve',$lr) }}" method="POST" class="d-inline form-approve">@csrf @method('PATCH')
          <button type="button" class="btn btn-sm btn-success btn-pill btn-approve"><i class="fas fa-check"></i> Approve</button>
        </form>
        <form action="{{ route('admin.leave-requests.reject',$lr) }}" method="POST" class="d-inline form-reject">@csrf @method('PATCH')
          <button type="button" class="btn btn-sm btn-danger btn-pill btn-reject"><i class="fas fa-times"></i> Reject</button>
        </form>
      @endif
    </td>
  </tr>
  @empty
  <tr><td colspan="8" class="text-center text-muted">Belum ada data</td></tr>
  @endforelse
</tbody>

      </table>
    </div>
    @if($leaveRequests instanceof \Illuminate\Pagination\LengthAwarePaginator)
      <div class="card-body">{{ $leaveRequests->withQueryString()->links() }}</div>
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Approve
  document.querySelectorAll('.btn-approve').forEach(btn=>{
    btn.addEventListener('click',function(){
      Swal.fire({title:'Approve pengajuan ini?', icon:'question', showCancelButton:true, confirmButtonText:'Approve'})
        .then(res=>{ if(res.isConfirmed){ this.closest('form').submit(); }});
    });
  });
  // Reject
  document.querySelectorAll('.btn-reject').forEach(btn=>{
    btn.addEventListener('click',function(){
      Swal.fire({
        title:'Tolak pengajuan ini?', input:'text', inputPlaceholder:'Alasan penolakan (opsional)',
        showCancelButton:true, confirmButtonText:'Reject'
      }).then(res=>{
        if(res.isConfirmed){
          const form = this.closest('form');
          const i = document.createElement('input'); i.type='hidden'; i.name='reason'; i.value=res.value||'';
          form.appendChild(i); form.submit();
        }
      });
    });
  });
</script>
@endpush
