@extends('layouts.master')

@section('title','Pengajuan Absensi')

@section('content')
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow rounded-4">
        <div class="card-header bg-info text-white text-center rounded-top-4">
          <h4 class="my-2">Form Pengajuan Absensi</h4>
        </div>
        <div class="card-body p-4">
          @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
          @endif

          <form action="{{ route('employee.attendance.requests.store') }}" method="POST">
            @csrf

            {{-- Tanggal --}}
            <div class="mb-3">
              <label for="date" class="form-label">Tanggal</label>
              <input type="date" name="date" id="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date') }}" required>
              @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Tipe --}}
            <div class="mb-3">
              <label for="type" class="form-label">Jenis Pengajuan</label>
              <select name="type" id="type" class="form-control @error('type') is-invalid @enderror" required>
                <option value="">-- Pilih Tipe --</option>
                <option value="check_in" {{ old('type') == 'check_in' ? 'selected' : '' }}>Masuk</option>
                <option value="check_out" {{ old('type') == 'check_out' ? 'selected' : '' }}>Pulang</option>
                <option value="check_in_out" {{ old('type') == 'check_in_out' ? 'selected' : '' }}>Masuk & Pulang</option>
              </select>

              @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Alasan --}}
            <div class="mb-3">
              <label for="reason" class="form-label">Alasan (Opsional)</label>
              <textarea name="reason" id="reason" rows="3" class="form-control @error('reason') is-invalid @enderror" placeholder="Contoh: Sakit, lupa presensi..."></textarea>
              @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-success w-100 py-2">Kirim Pengajuan</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
