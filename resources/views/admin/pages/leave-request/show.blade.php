@extends('layouts.master')
@section('title','Detail Pengajuan Cuti')
@include('components.leave.styles-soft')


@section('content')
<div class="container-fluid">

  {{-- HERO --}}
  <div class="card hero-card mb-4">
    <div class="hero-body d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between">
      <div>
        <div class="h3 mb-1 fw-bold">{{ $leave->employee?->name ?? '—' }}</div>
        <div class="d-flex flex-wrap gap-2">
          <span class="chip"><i class="fas fa-briefcase"></i> {{ $leave->employee?->department?->name ?? '—' }}</span>
          <span class="chip"><i class="fas fa-tags"></i> {{ $leave->type?->name ?? '—' }}</span>
          <span class="chip">@include('components.leave.status-badge',['status'=>$leave->status])</span>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.leave-requests.index') }}" class="btn btn-soft-white btn-pill btn-elev"><i class="fas fa-arrow-left"></i> Kembali</a>
        @if($leave->status === 'pending')
          <form action="{{ route('admin.leave-requests.approve',$leave) }}" method="POST" class="d-inline form-approve">@csrf @method('PATCH')
            <button type="button" class="btn btn-light btn-pill btn-elev btn-approve"><i class="fas fa-check"></i> Approve</button>
          </form>
          <form action="{{ route('admin.leave-requests.reject',$leave) }}" method="POST" class="d-inline form-reject">@csrf @method('PATCH')
            <button type="button" class="btn btn-danger btn-pill btn-elev btn-reject"><i class="fas fa-times"></i> Reject</button>
          </form>
        @endif
      </div>
    </div>
  </div>

  <div class="row g-3">
    {{-- KIRI: Info Pengajuan --}}
    <div class="col-lg-6">
      <div class="card soft mb-3">
        <div class="card-header">Informasi Pengajuan</div>
        <div class="card-body">
          <dl class="dl-grid clearfix mb-0">
            <dt>Periode</dt><dd>{{ optional($leave->start_date)->format('d M Y') }} – {{ optional($leave->end_date)->format('d M Y') }}</dd>
            <dt>Jumlah Hari</dt><dd>{{ rtrim(rtrim(number_format($leave->days ?? 0, 2, ',', '.'), '0'), ',') }} hari</dd>
            <dt>Tipe Cuti</dt><dd><span class="badge badge-soft">{{ $leave->type?->name ?? '—' }}</span></dd>
            <dt>Status</dt><dd>@include('components.leave.status-badge',['status'=>$leave->status])</dd>
            <dt>Alasan</dt><dd>{{ $leave->reason ?: '—' }}</dd>
            <dt>Lampiran</dt>
            <dd>
              @if($leave->attachment_path)
                <a href="{{ asset('storage/'.$leave->attachment_path) }}" target="_blank" class="btn btn-sm btn-light btn-pill">
                  <i class="fas fa-paperclip"></i> Lihat Lampiran
                </a>
              @else — @endif
            </dd>
            <dt>Dibuat</dt><dd>{{ optional($leave->created_at)->format('d M Y H:i') }}</dd>
            <dt>Terakhir Diperbarui</dt><dd>{{ optional($leave->updated_at)->format('d M Y H:i') }}</dd>
          </dl>
        </div>
      </div>

      <div class="card soft mb-3">
        <div class="card-header">Saldo Cuti Karyawan (Saat Ini)</div>
        <div class="card-body">
          <dl class="dl-grid clearfix mb-0">
            <dt>Terpakai Tahun Ini</dt><dd>{{ $balances['used'] ?? '—' }} hari</dd>
            <dt>Sisa Kuota</dt><dd>{{ $balances['remaining'] ?? '—' }} hari</dd>
          </dl>
        </div>
      </div>
    </div>

    {{-- KANAN: Timeline Approval --}}
    <div class="col-lg-6">
      <div class="card soft mb-3">
        <div class="card-header">Timeline Approval</div>
        <div class="card-body">
          @forelse($leave->approvals as $ap)
            <div class="d-flex align-items-start gap-3 mb-3">
              <div class="text-secondary"><i class="fas fa-circle"></i></div>
              <div>
                <div class="fw-semibold">{{ ucfirst($ap->status) }} @ {{ optional($ap->created_at)->format('d M Y H:i') }}</div>
                <div class="text-muted small">Oleh: {{ $ap->approver?->name ?? '—' }} (Level {{ $ap->level ?? 1 }})</div>
                @if($ap->note)<div class="small mt-1">Catatan: {{ $ap->note }}</div>@endif
              </div>
            </div>
          @empty
            <div class="text-muted">Belum ada jejak approval.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.querySelectorAll('.btn-approve').forEach(btn=>{
    btn.addEventListener('click',function(){
      Swal.fire({title:'Approve pengajuan ini?', icon:'question', showCancelButton:true, confirmButtonText:'Approve'})
        .then(res=>{ if(res.isConfirmed){ this.closest('form').submit(); }});
    });
  });
  document.querySelectorAll('.btn-reject').forEach(btn=>{
    btn.addEventListener('click',function(){
      Swal.fire({title:'Tolak pengajuan ini?', input:'text', inputPlaceholder:'Alasan (opsional)', showCancelButton:true, confirmButtonText:'Reject'})
        .then(res=>{ if(res.isConfirmed){ const f=this.closest('form'); const i=document.createElement('input'); i.type='hidden'; i.name='reason'; i.value=res.value||''; f.appendChild(i); f.submit(); }});
    });
  });
</script>
@endpush
