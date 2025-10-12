<!-- resources/views/admin/pages/attendance-location-settings/edit.blade.php -->

@extends('layouts.master')

@section('title', 'Edit Lokasi Absensi')

@section('content')
<div class="container-fluid py-4">
  <div class="card border-0 shadow rounded-xl">
    <div class="card-header bg-gradient-warning text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">✏️ Edit Lokasi Absensi</h5>
    </div>

    <div class="card-body">
      <form id="editForm" action="{{ route('admin.attendance-location-settings.update', $location->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row mb-3">
          <div class="col-md-6">
            <label for="location_name" class="form-label">Nama Lokasi</label>
            <input type="text" name="location_name" id="location_name" class="form-control" value="{{ old('location_name', $location->location_name) }}" required>
          </div>
          <div class="col-md-6">
            <label for="radius" class="form-label">Radius (meter)</label>
            <input type="number" name="radius" id="radius" class="form-control" min="1" max="1000" value="{{ old('radius', $location->radius) }}" required>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label">Pilih Lokasi pada Peta</label>
          <div id="map" class="w-100 rounded shadow border" style="height: 400px;"></div>
          <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $location->latitude) }}">
          <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $location->longitude) }}">
        </div>

        <div class="table-responsive">
          <table class="table table-bordered">
            <thead class="table-light">
              <tr>
                <th>Nama Lokasi</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Radius</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td id="preview_name">{{ $location->location_name }}</td>
                <td id="preview_lat">{{ $location->latitude }}</td>
                <td id="preview_lng">{{ $location->longitude }}</td>
                <td id="preview_radius">{{ $location->radius }} meter</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4">
          <button type="button" class="btn btn-warning text-white" onclick="confirmSubmit()">Simpan Perubahan</button>
          <a href="{{ route('admin.attendance-location-settings.index') }}" class="btn btn-outline-secondary ms-2">Batal</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDY0kvZF3ujvuKeiDgch18wfM8-Mt0fBOE"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  let map, marker, circle;
  const lat = parseFloat("{{ $location->latitude }}");
  const lng = parseFloat("{{ $location->longitude }}");
  const radiusInput = document.getElementById('radius');

  function initMap() {
    const center = { lat, lng };
    map = new google.maps.Map(document.getElementById("map"), {
      center,
      zoom: 15,
    });

    marker = new google.maps.Marker({
      position: center,
      map,
      draggable: true,
    });

    circle = new google.maps.Circle({
      strokeColor: '#ffc107',
      strokeOpacity: 0.8,
      strokeWeight: 2,
      fillColor: '#ffc107',
      fillOpacity: 0.35,
      map,
      center,
      radius: parseFloat(radiusInput.value),
    });

    marker.addListener("dragend", function (e) {
      const lat = e.latLng.lat();
      const lng = e.latLng.lng();
      document.getElementById("latitude").value = lat;
      document.getElementById("longitude").value = lng;
      circle.setCenter({ lat, lng });
      updatePreview();
    });

    map.addListener("click", function (e) {
      const lat = e.latLng.lat();
      const lng = e.latLng.lng();
      marker.setPosition({ lat, lng });
      circle.setCenter({ lat, lng });
      document.getElementById("latitude").value = lat;
      document.getElementById("longitude").value = lng;
      updatePreview();
    });

    radiusInput.addEventListener('input', function () {
      circle.setRadius(parseFloat(this.value || 0));
      updatePreview();
    });
  }

  function updatePreview() {
    document.getElementById('preview_name').textContent = document.getElementById('location_name').value;
    document.getElementById('preview_lat').textContent = document.getElementById('latitude').value;
    document.getElementById('preview_lng').textContent = document.getElementById('longitude').value;
    document.getElementById('preview_radius').textContent = radiusInput.value + ' meter';
  }

  function confirmSubmit() {
    if (!document.getElementById('location_name').value || !radiusInput.value) {
      Swal.fire('Validasi Gagal', 'Semua kolom wajib diisi!', 'warning');
      return;
    }
    if (parseInt(radiusInput.value) < 10) {
      Swal.fire('Validasi Radius', 'Radius minimal adalah 10 meter.', 'warning');
      return;
    }

    Swal.fire({
      icon: 'question',
      title: 'Simpan perubahan?',
      showCancelButton: true,
      confirmButtonText: 'Ya, Simpan',
      cancelButtonText: 'Batal'
    }).then(result => {
      if (result.isConfirmed) {
        document.getElementById('editForm').submit();
      }
    });
  }

  window.onload = initMap;
</script>
@endpush