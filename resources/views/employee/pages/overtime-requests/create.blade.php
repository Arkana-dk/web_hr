{{-- resources/views/employee/overtime/create.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="container py-4">
    <div class="card shadow rounded-4">
        <div class="card-header bg-primary text-white rounded-top-4">
            <h5 class="mb-0">Form Pengajuan Lembur</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('employee.overtime.requests.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="date" class="form-label">Tanggal Lembur</label>
                    <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" required>
                    @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row mb-3">
                    <div class="col">
                        <label for="start_time" class="form-label">Jam Mulai</label>
                        <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" required>
                        @error('start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col">
                        <label for="end_time" class="form-label">Jam Selesai</label>
                        <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror" required>
                        @error('end_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="reasom" class="form-label">Alasan Pekerjaan</label>
                    <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3" required></textarea>
                    @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                  <label for="day_type" class="form-label">Tipe Hari</label>
                  <select name="day_type" id="day_type" class="form-control" required>
                    <option value="">-- Pilih Tipe Hari --</option>
                    <option value="weekday">Hari Kerja</option>
                    <option value="weekend">Akhir Pekan</option>
                    <option value="holiday">Hari Libur</option>
                  </select>
                </div>


                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="meal_option" id="meal_option" value="1">
                    <label class="form-check-label" for="meal">Mendapat Makan</label>
                    
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="meal_option" id="meal_option" value="1">
                    <label class="form-check-label" for="meal">Tidak Mendapat Makan</label>
                </div>
                <div class="mb-3">
                <label for="transport_route" class="form-label">Rute Transportasi</label>
                <select name="transport_route" id="transport_route" class="form-control" required>
                  <option value="">-- Pilih Rute --</option>
                  @foreach($transportRoutes as $route)
                    <option value="{{ $route->route_name }}">{{ $route->route_name }}</option>
                  @endforeach
                </select>
              </div>


                <button type="submit" class="btn btn-success w-100">Ajukan Lembur</button>
            </form>
        </div>
    </div>
</div>
@endsection
@push('scripts')
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

    @if(session('error'))
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: '{{ session('error') }}'
      });
    @endif
  });
</script>
@endpush


