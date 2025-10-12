@extends('layouts.master')

@section('title', 'Tambah Lokasi Absensi')

@section('content')
<div class="container-fluid py-4">
  <div class="card border-0 shadow rounded-xl">
    <div class="card-header bg-gradient-success text-white rounded-top">
      <h5 class="mb-0">📌 Tambah Lokasi Absensi</h5>
    </div>
    <div class="card-body">
      <form action="{{ route('admin.attendance-location-settings.store') }}" method="POST">
        @csrf

        <div class="row mb-4">
          <div class="col-md-6">
            <label for="location_name" class="form-label fw-semibold">Nama Lokasi</label>
            <input type="text" name="location_name" id="location_name" class="form-control" value="{{ old('location_name') }}" required>
          </div>
          <div class="col-md-6">
            <label for="radius" class="form-label fw-semibold">Radius (meter)</label>
            <input type="number" name="radius" id="radius" class="form-control" min="1" max="1000" value="{{ old('radius', 100) }}" required>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label fw-semibold">Pilih Lokasi Pada Peta</label>
          <div id="map" class="rounded shadow border" style="height: 400px"></div>
          <input type="hidden" name="latitude" id="latitude" required>
          <input type="hidden" name="longitude" id="longitude" required>
        </div>

        <div class="mb-4">
          <label class="form-label fw-semibold">Preview Lokasi</label>
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead class="table-light">
                <tr>
                  <th>Latitude</th>
                  <th>Longitude</th>
                  <th>Radius (m)</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td id="preview-lat">-</td>
                  <td id="preview-lng">-</td>
                  <td id="preview-radius">-</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="d-flex justify-content-end">
          <a href="{{ route('admin.attendance-location-settings.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
          <button type="submit" class="btn btn-success">💾 Simpan Lokasi</button>
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

  function updatePreview(lat, lng, radius) {
    document.getElementById('latitude').value = lat;
    document.getElementById('longitude').value = lng;
    document.getElementById('preview-lat').innerText = lat.toFixed(6);
    document.getElementById('preview-lng').innerText = lng.toFixed(6);
    document.getElementById('preview-radius').innerText = radius;
  }

  function initMap() {
    const defaultPosition = { lat: -6.200000, lng: 106.816666 };
    const radiusInput = document.getElementById('radius');

    map = new google.maps.Map(document.getElementById("map"), {
      center: defaultPosition,
      zoom: 13,
    });

    marker = new google.maps.Marker({
      position: defaultPosition,
      map: map,
      draggable: true,
    });

    circle = new google.maps.Circle({
      map: map,
      radius: parseInt(radiusInput.value),
      fillColor: '#93c5fd',
      fillOpacity: 0.4,
      strokeColor: '#2563eb',
      strokeOpacity: 0.8,
      strokeWeight: 2,
    });
    circle.bindTo('center', marker, 'position');

    updatePreview(defaultPosition.lat, defaultPosition.lng, radiusInput.value);

    map.addListener('click', function(event) {
      marker.setPosition(event.latLng);
      map.panTo(event.latLng);
      updatePreview(event.latLng.lat(), event.latLng.lng(), radiusInput.value);
    });

    marker.addListener('dragend', function(event) {
      updatePreview(event.latLng.lat(), event.latLng.lng(), radiusInput.value);
    });

    radiusInput.addEventListener('input', function() {
      const newRadius = parseInt(this.value);
      circle.setRadius(newRadius);
      updatePreview(marker.getPosition().lat(), marker.getPosition().lng(), newRadius);
    });
  }

  window.onload = initMap;

  @if($errors->any())
  Swal.fire({
    icon: 'error',
    title: 'Gagal Menyimpan',
    html: `{!! implode('<br>', $errors->all()) !!}`,
    confirmButtonText: 'OK',
    customClass: {
      popup: 'text-start'
    }
  });
  @endif
</script>
@endpush
