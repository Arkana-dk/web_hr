@extends('layouts.master')

@section('title','Tambah Pengajuan Lembur')

@section('content')
<div class="container-fluid">
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-clock"></i> Tambah Pengajuan Lembur</h1>
    <a href="{{ route('admin.overtime-requests.index') }}" class="btn btn-secondary">
      <i class="fas fa-arrow-left"></i> Kembali
    </a>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <h6 class="m-0 font-weight-bold text-primary">Form Pengajuan Lembur</h6>
    </div>
    <div class="card-body">
      <form action="{{ route('admin.overtime-requests.store') }}" method="POST">
        @csrf

        {{-- Pilih Pegawai --}}
        <div class="form-group">
          <label for="employee_id"><i class="fas fa-user"></i> Cari Pegawai (NIK/Nama)</label>
          <select name="employee_id" id="employee_id" class="form-control" required>
            <option value="">-- Pilih Pegawai --</option>
            @foreach ($employees as $employee)
              <option value="{{ $employee->id }}"
                data-nik="{{ $employee->nik }}"
                data-name="{{ $employee->name }}"
                data-department="{{ $employee->department->name ?? 'Tidak tersedia' }}"
                data-position="{{ $employee->position->name ?? 'Tidak tersedia' }}">
                {{ $employee->nik }} - {{ $employee->name }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Info Pegawai --}}
        <div class="row">
          <div class="col-md-4">
            <div class="border p-2 rounded">
              <label class="text-muted">NIK</label>
              <p class="font-weight-bold mb-0" id="employee_nik">-</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border p-2 rounded">
              <label class="text-muted">Departemen</label>
              <p class="font-weight-bold mb-0" id="employee_department">-</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border p-2 rounded">
              <label class="text-muted">Posisi</label>
              <p class="font-weight-bold mb-0" id="employee_position">-</p>
            </div>
          </div>
        </div>

        <hr>

        <div class="form-row">
          {{-- Tanggal --}}
          <div class="form-group col-md-4">
            <label for="date"><i class="fas fa-calendar-alt"></i> Tanggal</label>
            <input type="date" name="date" id="date" class="form-control" value="{{ old('date') }}" required>
          </div>

          {{-- Jam Mulai --}}
          <div class="form-group col-md-4">
            <label for="start_time"><i class="fas fa-play-circle"></i> Jam Mulai</label>
            <input type="time" name="start_time" id="start_time" class="form-control" value="{{ old('start_time') }}" required>
          </div>

          {{-- Jam Selesai --}}
          <div class="form-group col-md-4">
            <label for="end_time"><i class="fas fa-stop-circle"></i> Jam Selesai</label>
            <input type="time" name="end_time" id="end_time" class="form-control" value="{{ old('end_time') }}" required>
          </div>

          {{-- Jenis Hari --}}
          <div class="form-group col-md-4">
            <label for="day_type"><i class="fas fa-calendar-day"></i> Jenis Hari</label>
            <select name="day_type" id="day_type" class="form-control" required>
              <option value="Awal" {{ old('day_type') == 'Awal' ? 'selected' : '' }}>Awal</option>
              <option value="Libur" {{ old('day_type') == 'Libur' ? 'selected' : '' }}>Libur</option>
              <option value="Akhir" {{ old('day_type') == 'Akhir' ? 'selected' : '' }}>Akhir</option>
            </select>
          </div>

          {{-- Pilihan Makan --}}
          <div class="form-group col-md-4">
            <label for="meal_option"><i class="fas fa-utensils"></i> Pilihan Makan</label>
            <select name="meal_option" id="meal_option" class="form-control" required>
              <option value="Makan" {{ old('meal_option') == 'Makan' ? 'selected' : '' }}>Makan</option>
              <option value="Tidak Makan" {{ old('meal_option') == 'Tidak Makan' ? 'selected' : '' }}>Tidak Makan</option>
            </select>
          </div>

          {{-- Transportasi --}}
          <div class="form-group col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label for="transport_route" class="mb-0"><i class="fas fa-bus"></i> Jurusan Transportasi</label>
              <a href="{{ route('admin.transportroutes.create') }}" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-plus"></i> Tambah
              </a>
            </div>
            <select name="transport_route" id="transport_route" class="form-control" required>
              @foreach ($transportRoutes as $route)
                <option value="{{ $route->route_name }}" {{ old('transport_route') == $route->route_name ? 'selected' : '' }}>
                  {{ $route->route_name }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- Alasan --}}
          <div class="form-group col-md-12">
            <label for="reason"><i class="fas fa-align-left"></i> Alasan</label>
            <textarea name="reason" id="reason" class="form-control" rows="4" required>{{ old('reason') }}</textarea>
          </div>
        </div>

        <button type="submit" class="btn btn-success btn-block mt-3">
          <i class="fas fa-paper-plane"></i> Submit Pengajuan
        </button>
      </form>
    </div>
  </div>
</div>
@endsection
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
  .select2-container--default .select2-selection--single {
    height: 38px;
    padding: 6px 12px;
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
  }
  .select2-selection__arrow {
    height: 36px !important;
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  $(document).ready(function () {
    $('#employee_id').select2({
      placeholder: "Cari NIK atau Nama...",
      width: '100%'
    });

    $('#employee_id').on('change', function () {
      const selected = $(this).find('option:selected');
      $('#employee_nik').text(selected.data('nik') || '-');
      $('#employee_department').text(selected.data('department') || '-');
      $('#employee_position').text(selected.data('position') || '-');
    });

    $('#employee_id').trigger('change');
  });
</script>
@endpush
