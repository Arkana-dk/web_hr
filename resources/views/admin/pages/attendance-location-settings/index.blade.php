@extends('layouts.master')

@section('title', 'Attendance Location Settings')

@section('content')
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">📍 Daftar Lokasi Absensi</h4>
    <a href="{{ route('admin.attendance-location-settings.create') }}" class="btn btn-primary">
      + Tambah Lokasi
    </a>
  </div>

  <div class="card shadow-sm rounded mb-4">
    <div class="card-body p-0">
      <div id="map" class="w-100" style="height: 400px"></div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-gradient-primary text-white">
      <h6 class="mb-0">📋 Tabel Lokasi Absensi</h6>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Nama Lokasi</th>
              <th>Latitude</th>
              <th>Longitude</th>
              <th>Radius (m)</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($locations as $index => $loc)
              <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $loc->location_name }}</td>
                <td>{{ $loc->latitude }}</td>
                <td>{{ $loc->longitude }}</td>
                <td>{{ $loc->radius }}</td>
                <td>
                  <a href="{{ route('admin.attendance-location-settings.edit', $loc->id) }}" class="btn btn-sm btn-warning">Edit</a>
                  <form action="{{ route('admin.attendance-location-settings.destroy', $loc->id) }}" method="POST" class="d-inline delete-form">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted">Belum ada data lokasi.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDY0kvZF3ujvuKeiDgch18wfM8-Mt0fBOE"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  function initMap() {
    const map = new google.maps.Map(document.getElementById('map'), {
      zoom: 12,
      center: { lat: -6.200000, lng: 106.816666 },
    });

    const locations = @json($locations);

    locations.forEach(loc => {
      const position = {
        lat: parseFloat(loc.latitude),
        lng: parseFloat(loc.longitude),
      };

      const marker = new google.maps.Marker({
        position,
        map,
        title: loc.location_name,
        icon: {
          path: google.maps.SymbolPath.CIRCLE,
          scale: 6,
          fillColor: '#2563EB',
          fillOpacity: 1,
          strokeWeight: 1,
          strokeColor: '#ffffff'
        }
      });

      new google.maps.Circle({
        strokeColor: '#2563EB',
        strokeOpacity: 0.6,
        strokeWeight: 1,
        fillColor: '#60A5FA',
        fillOpacity: 0.25,
        map,
        center: position,
        radius: parseFloat(loc.radius),
      });
    });
  }

  window.onload = initMap;

  document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      Swal.fire({
        title: 'Yakin ingin menghapus?',
        text: 'Data yang dihapus tidak dapat dikembalikan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
</script>
@endpush
