@extends('layouts.master')

@section('title','Riwayat Presensi')

@section('content')
<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Riwayat Presensi</h1>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="table-responsive">
    <table class="table table-bordered">
      <thead class="thead-light">
        <tr>
          <th>No</th>
          <th>Tanggal</th>
          <th>Check In</th>
          <th>Check Out</th>
          <th>Lokasi</th>
          <th>Status</th>
          <th>Catatan</th>
          <th>Foto</th>
        </tr>
      </thead>
      <tbody>
        @forelse($attendances as $att)
          <tr>
            <td>{{ $attendances->firstItem() + $loop->index }}</td>
            <td>{{ \Carbon\Carbon::parse($att->date)->format('d M Y') }}</td>
            <td>{{ $att->check_in_time }}</td>
            <td>{{ $att->check_out_time ?? '—' }}</td>
            <td>{{ $att->check_in_location ?? '—' }}</td>
            <td>{{ ucfirst($att->status) }}</td>
            <td>{{ $att->notes ?? '—' }}</td>
            <td>
              @if($att->photo_path)
                <img src="{{ asset('storage/'.$att->photo_path) }}"
                     style="width:40px;height:40px;object-fit:cover;border-radius:4px;">
              @else
                &mdash;
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="text-center">Belum ada data.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="d-flex justify-content-center">
    {{ $attendances->links('pagination::bootstrap-4') }}
  </div>

  <a href="{{ route('employee.presensi.request') }}"
     class="btn btn-primary mt-4">
    <i class="fas fa-paper-plane"></i> Isi Presensi Baru
  </a>
</div>
@endsection
