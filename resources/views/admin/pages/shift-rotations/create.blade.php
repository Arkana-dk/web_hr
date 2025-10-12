@extends('layouts.master')

@section('title', 'Tambah Shift Rotation')

@section('content')
<div class="container-fluid">
  <div class="card shadow-sm">
    <div class="card-body">
      <h4 class="mb-4">➕ Tambah Shift Rotation</h4>

      <form action="{{ route('admin.shift-rotations.store') }}" method="POST">
        @csrf

        {{-- Group --}}
        <div class="mb-3">
          <label for="group_id" class="form-label">Group</label>
          <select class="form-select" name="group_id" id="group_id" required>
            <option value="" disabled selected>-- Pilih Group --</option>
            @foreach ($groups as $group)
              <option value="{{ $group->id }}">{{ $group->name }}</option>
            @endforeach
          </select>
        </div>

        {{-- Shift --}}
        <div class="mb-3">
          <label for="shift_id" class="form-label">Shift</label>
          <select class="form-select" name="shift_id" id="shift_id">
            <option value="" selected>-- Pilih Shift (Kosong artinya libur) --</option>
            @foreach ($shifts as $shift)
              <option value="{{ $shift->id }}">{{ $shift->name }} ({{ $shift->start_time }} - {{ $shift->end_time }})</option>
            @endforeach
          </select>
          <div class="form-text text-muted">Kosongkan shift jika minggu ini adalah minggu libur</div>
        </div>

        {{-- Order --}}
        <div class="mb-3">
          <label for="order" class="form-label">Minggu Ke (Order)</label>
          <input type="number" name="order" id="order" class="form-control" required placeholder="Contoh: 1, 2, 3 ...">
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-primary">Simpan</button>
          <a href="{{ route('admin.shift-rotations.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
