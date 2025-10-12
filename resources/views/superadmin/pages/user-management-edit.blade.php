{{-- resources/views/superadmin/pages/user-management-edit.blade.php --}}

@extends('layouts.master')
@section('title','Edit Pengguna')

@section('content')
<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Edit Pengguna: {{ $user->name }}</h1>

  {{-- Validation Errors --}}
  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form action="{{ route('superadmin.users.update', $user) }}" method="POST">
    @csrf @method('PUT')

    <div class="form-group">
      <label for="name">Nama</label>
      <input id="name" type="text"
             name="name"
             class="form-control @error('name') is-invalid @enderror"
             value="{{ old('name', $user->name) }}"
             required>
      @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group">
      <label for="email">Email</label>
      <input id="email" type="email"
             name="email"
             class="form-control @error('email') is-invalid @enderror"
             value="{{ old('email', $user->email) }}"
             required>
      @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group">
      <label for="password">Password Baru <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
      <input id="password" type="password"
             name="password"
             class="form-control @error('password') is-invalid @enderror">
      @error('password')
        <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group">
      <label for="password_confirmation">Konfirmasi Password</label>
      <input id="password_confirmation" type="password"
             name="password_confirmation"
             class="form-control">
    </div>

    <div class="form-group">
      <label for="role">Role</label>
      <select id="role"
              name="role"
              class="form-control @error('role') is-invalid @enderror"
              required>
        @foreach([
          'superadmin' => 'Superadmin',
          'admin'      => 'Admin',
          'user'       => 'User',
        ] as $value => $label)
          <option value="{{ $value }}"
                  {{ old('role', $user->role) === $value ? 'selected' : '' }}>
            {{ $label }}
          </option>
        @endforeach
      </select>
      @error('role')
        <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    </div>

    <button type="submit" class="btn btn-success">
      <i class="fas fa-save"></i> Update
    </button>
    <a href="{{ route('superadmin.users.index') }}" class="btn btn-secondary">
      <i class="fas fa-arrow-left"></i> Batal
    </a>
  </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@if(session('success'))
<script>
  Swal.fire({
    icon: 'success',
    title: 'Berhasil',
    text: @json(session('success')),
    timer: 2000,
    showConfirmButton: false
  });
</script>
@endif
@endpush
