{{-- resources/views/admin/departments/edit.blade.php --}}
@extends('layouts.master')

@section('title','Edit Department')

@section('content')
<div class="container-fluid">
  <div class="row justify-content-center">
    <div class="col-lg-6 col-md-10">
      <div class="card shadow mb-4">
        <div class="card-header bg-warning text-white d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
          <h6 class="m-0 font-weight-bold mb-2 mb-md-0">Edit Department</h6>
          <a href="{{ route('admin.departments.index') }}" class="btn btn-light btn-sm rounded-pill px-3">
            <i class="fas fa-arrow-left"></i> <span class="d-none d-md-inline">Back to List</span>
          </a>
        </div>

        <div class="card-body">
          @if($errors->any())
            <div class="alert alert-danger mb-3">
              <ul class="mb-0">
                @foreach($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form action="{{ route('admin.departments.update', $department) }}" method="POST" id="form-edit-dept" novalidate>
            @csrf
            @method('PUT')

            <div class="form-group">
              <label for="name">Name</label>
              <input
                id="name"
                type="text"
                name="name"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $department->name) }}"
                placeholder="Department Name"
                required
                minlength="2"
                maxlength="100"
                autofocus
              >
              @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="d-flex flex-column flex-sm-row gap-2">
              <button type="submit" class="btn btn-warning rounded-pill px-4 mr-sm-2" id="btn-submit">
                <span class="btn-text"><i class="fas fa-save"></i> Update</span>
                <span class="spinner-border spinner-border-sm align-text-bottom d-none" role="status" aria-hidden="true"></span>
              </button>
              <a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary rounded-pill px-4 mt-2 mt-sm-0">
                Cancel
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.addEventListener('DOMContentLoaded', function(){
    // Why: Prevent double-submit & give feedback
    const form = document.getElementById('form-edit-dept');
    const submitBtn = document.getElementById('btn-submit');
    if (form && submitBtn) {
      form.addEventListener('submit', function(){
        const spinner = submitBtn.querySelector('.spinner-border');
        const text = submitBtn.querySelector('.btn-text');
        submitBtn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');
        if (text) text.classList.add('opacity-75');
      });
    }

    // Success toast (if any)
    @if(session('success'))
    Swal.fire({
      icon: 'success',
      title: 'Berhasil',
      text: @json(session('success')),
      timer: 2000,
      toast: true,
      position: 'top-end',
      showConfirmButton: false
    });
    @endif
  });
</script>
@endpush
