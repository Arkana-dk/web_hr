@extends('layouts.master')

@section('title', 'Edit Group')

@section('content')
<div class="container-fluid">
  <div class="card shadow mb-4">
    <div class="card-header bg-warning text-white">
      <h6 class="m-0 font-weight-bold">Edit Group</h6>
    </div>

    <div class="card-body">
      <form id="editGroupForm" action="{{ route('admin.groups.update', $group->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
          <label for="name">Group Name</label>
          <input type="text" name="name" class="form-control" value="{{ old('name', $group->name) }}" required>
        </div>

        <button type="button" class="btn btn-primary" id="btn-confirm-edit">Update Group</button>
        <a href="{{ route('admin.groups.index') }}" class="btn btn-secondary">Cancel</a>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.getElementById('btn-confirm-edit').addEventListener('click', function () {
    Swal.fire({
      title: 'Yakin ingin mengubah group ini?',
      text: 'Perubahan akan disimpan permanen.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, simpan perubahan',
      cancelButtonText: 'Batal',
    }).then((result) => {
      if (result.isConfirmed) {
        document.getElementById('editGroupForm').submit();
      }
    });
  });
</script>
@endpush
