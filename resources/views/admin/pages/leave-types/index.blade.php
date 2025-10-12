@extends('layouts.master')
@section('title','Leave Types')
@include('components.leave.styles-soft')



@section('content')
<div class="container-fluid">
  <div class="card hero-card shadow-sm mb-4"><div class="hero-body d-flex justify-content-between align-items-center">
    <div><div class="h3 fw-bold mb-1">Leave Types</div><div class="opacity-75">Kelola jenis cuti</div></div>
    <a href="{{ route('admin.leave-types.create') }}" class="btn btn-light btn-pill btn-elev"><i class="fas fa-plus"></i> Tambah</a>
  </div></div>

  <div class="card soft">
    <div class="table-responsive">
      <table class="table table-hover align-middle table-soft mb-0">
        <thead>
  <tr>
    <th>Kode</th>
    <th>Nama</th>
    <th>Paid</th>
    <th>Butuh Lampiran</th>
    <th class="text-end">Aksi</th>
  </tr>
</thead>
<tbody>
@forelse($types as $t)
<tr>
  <td class="fw-semibold">{{ $t->code }}</td>
  <td>{{ $t->name }}</td>
  <td>{!! $t->is_paid ? '<span class="badge badge-soft">Ya</span>' : '—' !!}</td>
  <td>{!! $t->requires_attachment ? '<span class="badge badge-soft">Ya</span>' : '—' !!}</td>
  <td class="text-end">
    <a href="{{ route('admin.leave-types.edit',$t) }}" class="btn btn-sm btn-light btn-pill"><i class="fas fa-edit"></i> Edit</a>
    <form action="{{ route('admin.leave-types.destroy',$t) }}" method="POST" class="d-inline delete-form">
      @csrf @method('DELETE')
      <button type="button" class="btn btn-sm btn-danger btn-pill btn-delete"><i class="fas fa-trash"></i></button>
    </form>
  </td>
</tr>
@empty
<tr><td colspan="5" class="text-center text-muted">Belum ada data</td></tr>
@endforelse
</tbody>

      </table>
    </div>
    <div class="card-body">{{ $types->links() }}</div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll('.btn-delete').forEach(btn=>{
  btn.addEventListener('click',function(){
    Swal.fire({title:'Hapus jenis cuti?', icon:'warning', showCancelButton:true}).then(res=>{
      if(res.isConfirmed) this.closest('form').submit();
    });
  });
});
</script>
@endpush
