@extends('layouts.master')

@section('title', 'Pengajuan Pindah Shift')

@section('content')
<div class="container py-4">
  <div class="card shadow rounded-4">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">Form Pengajuan Pindah Shift</h5>
    </div>
    <div class="card-body">
      <form action="{{ route('employee.shift-change-requests.store') }}" method="POST">
        @csrf

        <div class="mb-3">
          <label for="date" class="form-label">Tanggal</label>
          <input type="date" name="date" id="date" class="form-control @error('date') is-invalid @enderror" required>
          @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
          <label for="to_shift_id" class="form-label">Shift Tujuan</label>
          <select name="to_shift_id" id="to_shift_id" class="form-control @error('to_shift_id') is-invalid @enderror" required>
            <option value="">-- Pilih Shift --</option>
            @foreach($shifts as $shift)
              <option value="{{ $shift->id }}">{{ $shift->name }} ({{ $shift->start_time }} - {{ $shift->end_time }})</option>
            @endforeach
          </select>
          @error('to_shift_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
          <label for="reason" class="form-label">Alasan (Opsional)</label>
          <textarea name="reason" id="reason" rows="3" class="form-control">{{ old('reason') }}</textarea>
        </div>

        <button type="submit" class="btn btn-success w-100">
          <i class="fas fa-paper-plane"></i> Kirim Pengajuan
        </button>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    @if(session('success'))
      Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: '{{ session('success') }}',
        timer: 2500,
        showConfirmButton: false
      });
    @endif

    @if($errors->any())
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: '{{ $errors->first() }}'
      });
    @endif
  });
</script>
@endsection
