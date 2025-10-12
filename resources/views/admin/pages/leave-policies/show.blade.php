@extends('layouts.master')
@section('title','Detail Policy')
@include('components.leave.styles-soft')

@section('content')
<div class="container-fluid">

  {{-- HERO --}}
  <div class="card hero-card mb-4">
    <div class="hero-body d-flex justify-content-between align-items-center flex-wrap">
      <div>
        <div class="h3 fw-bold mb-1">{{ $policy->name }}</div>
        <div class="opacity-75">
          {{ $policy->leaveType?->name ?? '—' }}
        </div>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('admin.leave-policies.index') }}" class="btn btn-soft-white btn-pill btn-elev">
          <i class="fas fa-arrow-left"></i> Kembali
        </a>
        <a href="{{ route('admin.leave-policies.edit',$policy) }}" class="btn btn-light btn-pill btn-elev">
          <i class="fas fa-edit"></i> Edit
        </a>
        <form action="{{ route('admin.leave-policies.destroy',$policy) }}" method="POST" class="d-inline delete-form">
          @csrf @method('DELETE')
          <button type="button" class="btn btn-danger btn-pill btn-elev btn-delete">
            <i class="fas fa-trash"></i> Hapus
          </button>
        </form>
      </div>
    </div>
  </div>

  {{-- SUMMARY STRIP --}}
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card stat-card h-100">
        <div class="stat-body">
          <div>
            <div class="stat-label">Kuota / Tahun</div>
            <div class="stat-value fs-5">{{ $policy->rules['annual_quota'] ?? '—' }}</div>
          </div>
          <i class="fas fa-calendar-alt text-secondary fs-4"></i>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card stat-card h-100">
        <div class="stat-body">
          <div>
            <div class="stat-label">Prorata</div>
            <div class="stat-value fs-5">
              {!! ($policy->rules['is_prorated'] ?? false) ? 'Ya' : 'Tidak' !!}
            </div>
          </div>
          <i class="fas fa-balance-scale text-secondary fs-4"></i>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card stat-card h-100">
        <div class="stat-body">
          <div>
            <div class="stat-label">Carry Over</div>
            <div class="stat-value fs-5">
              {!! ($policy->rules['allow_carry_over'] ?? false) ? 'Ya' : 'Tidak' !!}
            </div>
          </div>
          <i class="fas fa-sync-alt text-secondary fs-4"></i>
        </div>
      </div>
    </div>
  </div>

  {{-- DETAIL --}}
  <div class="card soft mb-3">
    <div class="card-header">Detail Policy</div>
    <div class="card-body">
      <dl class="dl-grid clearfix mb-0">
        <dt>Nama Policy</dt>
        <dd>{{ $policy->name }}</dd>

        <dt>Tipe Cuti</dt>
        <dd>{{ $policy->leaveType?->name ?? '—' }}</dd>

        <dt>Periode Efektif</dt>
        <dd>
          {{ $policy->effective_start?->format('d M Y') ?? '—' }}
          @if($policy->effective_end)
            – {{ $policy->effective_end->format('d M Y') }}
          @endif
        </dd>

        <dt>Cakupan</dt>
        <dd>
          @php
            $appliesTo  = $policy->rules['applies_to'] ?? 'all';
            $appliesVal = $policy->rules['applies_value'] ?? null;
          @endphp
          @if($appliesTo === 'all')
            Semua pegawai
          @else
            {{ ucfirst($appliesTo) }}: {{ $appliesVal }}
          @endif
        </dd>

        <dt>Dibuat</dt>
        <dd>{{ $policy->created_at?->format('d M Y H:i') ?? '—' }}</dd>

        <dt>Terakhir Diperbarui</dt>
        <dd>{{ $policy->updated_at?->format('d M Y H:i') ?? '—' }}</dd>
      </dl>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll('.btn-delete').forEach(btn=>{
  btn.addEventListener('click',function(){
    Swal.fire({
      title:'Hapus policy ini?',
      text:'Data tidak bisa dikembalikan.',
      icon:'warning',
      showCancelButton:true,
      confirmButtonText:'Ya, hapus',
      cancelButtonText:'Batal'
    }).then(res=>{
      if(res.isConfirmed) this.closest('form').submit();
    });
  });
});
</script>
@endpush
