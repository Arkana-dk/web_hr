@extends('layouts.master')
@section('title', 'Buat Group Shift')

@section('content')
<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Buat Group Shift</h1>

  <form action="{{ route('admin.shift-groups.store') }}" method="POST">
    @csrf
    <div class="form-group">
      <label>Nama Group</label>
      <input name="name"
             value="{{ old('name') }}"
             class="form-control @error('name') is-invalid @enderror"
             required>
      @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
      <label>Shift</label>
      <select name="shift_id"
              class="form-control @error('shift_id') is-invalid @enderror"
              required>
        <option value="">-- Pilih Shift --</option>
        @foreach($shifts as $shift)
          <option value="{{ $shift->id }}"
            {{ old('shift_id') == $shift->id ? 'selected' : '' }}>
            {{ $shift->name }} ({{ $shift->start_time }}–{{ $shift->end_time }})
          </option>
        @endforeach
      </select>
      @error('shift_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <button type="submit" class="btn btn-primary">Simpan</button>
  </form>
</div>
@endsection
