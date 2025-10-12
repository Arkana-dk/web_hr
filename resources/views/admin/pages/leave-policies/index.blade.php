@extends('layouts.master')
@section('title','Leave Policies')
@include('components.leave.styles-soft')

@section('content')
<div class="container-fluid">

  {{-- HERO HEADER --}}
  <div class="card hero-card mb-4">
    <div class="hero-body d-flex justify-content-between align-items-center">
      <div>
        <div class="h3 fw-bold mb-1">Leave Policies</div>
        <div class="opacity-75">Aturan kuota & cakupan</div>
      </div>
      <a href="{{ route('admin.leave-policies.create') }}" class="btn btn-light btn-pill btn-elev">
        <i class="fas fa-plus"></i> Tambah
      </a>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card soft">
    <div class="table-responsive">
      <table class="table table-hover align-middle table-soft mb-0">
        <thead>
          <tr>
            <th>Nama</th>
            <th>Tipe Cuti</th>
            <th>Kuota/Tahun</th>
            <th>Prorata</th>
            <th>Carry Over</th>
            <th>Periode</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($policies as $p)
          <tr>
            <td class="fw-semibold">
              <a href="{{ route('admin.leave-policies.show',$p) }}" class="text-decoration-none">
                {{ $p->name }}
              </a>
            </td>
            <td>{{ $p->leaveType?->name ?? '—' }}</td>
            <td>{{ $p->rules['annual_quota'] ?? '—' }}</td>
            <td>{!! ($p->rules['is_prorated'] ?? false) ? '<span class="badge badge-soft">Ya</span>' : '—' !!}</td>
            <td>{!! ($p->rules['allow_carry_over'] ?? false) ? '<span class="badge badge-soft">Ya</span>' : '—' !!}</td>
            <td>
              {{ $p->effective_start?->format('d M Y') ?? '—' }}
              @if($p->effective_end)
                – {{ $p->effective_end->format('d M Y') }}
              @endif
            </td>
            <td class="text-end">
              <a href="{{ route('admin.leave-policies.edit',$p) }}" class="btn btn-sm btn-light btn-pill">
                <i class="fas fa-edit"></i> Edit
              </a>
              <form action="{{ route('admin.leave-policies.destroy',$p) }}" method="POST" class="d-inline delete-form">
                @csrf @method('DELETE')
                <button type="button" class="btn btn-sm btn-danger btn-pill btn-delete">
                  <i class="fas fa-trash"></i>
                </button>
              </form>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="text-center text-muted">Belum ada data</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-body">
      {{ $policies->links() }}
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
      title:'Hapus policy?', 
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
