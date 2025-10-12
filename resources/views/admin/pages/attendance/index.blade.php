{{-- resources/views/admin/pages/attendance.blade.php --}}
@extends('layouts.master')

@section('title','Daftar Absensi')

@section('content')
<div class="container-fluid">
  <div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h6 class="m-0 font-weight-bold text-primary">Data Absensi</h6>
        @if(request('date') || request('status'))
          <small class="text-muted">
            Menampilkan:
            @if(request('date')) tanggal <strong>{{ request('date') }}</strong>@endif
            @if(request('status')) status <strong>{{ ucfirst(request('status')) }}</strong>@endif
          </small>
        @endif
      </div>
      <form method="GET" class="form-inline">
        <input type="date" name="date" value="{{ request('date') }}" class="form-control mr-2">
        <select name="status" class="form-control mr-2">
          <option value="">-- Semua Status --</option>
          <option value="present" {{ request('status')=='present'?'selected':'' }}>Hadir</option>
          <option value="late"    {{ request('status')=='late'   ?'selected':'' }}>Terlambat</option>
          <option value="absent"  {{ request('status')=='absent' ?'selected':'' }}>Tidak Hadir</option>
          <option value="excused" {{ request('status')=='excused'?'selected':'' }}>Izin</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary mr-2">Filter</button>
        @if(request()->hasAny(['date','status']))
          <a href="{{ route('admin.attendance.index') }}" class="btn btn-sm btn-secondary">Reset</a>
        @endif
      </form>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered" width="100%">
          <thead>
            <tr>
              <th>No</th>
              <th>Nama</th>
              <th>Tanggal</th>
              <th>Check-In</th>
              <th>Lokasi Masuk</th>
              <th>Check-Out</th>
              <th>Lokasi Pulang</th>
              <th>Status</th>
              <th>Foto Masuk</th>
              <th>Foto Pulang</th>
              <th>Catatan</th>
            </tr>
          </thead>

                  <tbody>
          @forelse($attendances as $attendance)
            <tr>
              <td>{{ $loop->iteration + ($attendances->currentPage() - 1) * $attendances->perPage() }}</td>
              <td>{{ $attendance->employee->name }}</td>
              
              {{-- Tanggal --}}
              <td>
                {{ \Carbon\Carbon::parse($attendance->date)->locale('id')->translatedFormat('d F Y') }}
              </td>
              
              {{-- Check-In --}}
              <td>
                {{ $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') : '–' }}
              </td>

              {{-- Lokasi Check-In --}}
              <td>
                @if($attendance->check_in_location)
                  <small>{{ $attendance->check_in_location }}</small><br>
                @endif
                @if($attendance->check_in_latitude && $attendance->check_in_longitude)
                  <small><a href="https://maps.google.com/?q={{ $attendance->check_in_latitude }},{{ $attendance->check_in_longitude }}" target="_blank">
                    📍 Maps
                  </a></small>
                @else
                  <span>–</span>
                @endif
              </td>

              {{-- Check-Out --}}
              <td>
                {{ $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('H:i') : '–' }}
              </td>

              {{-- Lokasi Check-Out --}}
              <td>
                @if($attendance->check_out_location)
                  <small>{{ $attendance->check_out_location }}</small><br>
                @endif
                @if($attendance->check_out_latitude && $attendance->check_out_longitude)
                  <small><a href="https://maps.google.com/?q={{ $attendance->check_out_latitude }},{{ $attendance->check_out_longitude }}" target="_blank">
                    📍 Maps
                  </a></small>
                @else
                  <span>–</span>
                @endif
              </td>

              {{-- Status --}}
              <td>
                @php
                  $color = match($attendance->status) {
                    'present' => 'success',
                    'late'    => 'warning',
                    'absent'  => 'danger',
                    'excused' => 'info',
                    default   => 'secondary',
                  };
                @endphp
                <span class="badge badge-{{ $color }}">
                  {{ ucfirst($attendance->status) }}
                </span>
              </td>

              {{-- Foto Masuk --}}
              <td>
                @if($attendance->check_in_photo_path)
                  <img src="{{ asset('storage/'.$attendance->check_in_photo_path) }}"
                      alt="Foto Masuk" width="60" class="img-thumbnail rounded">
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>


                {{-- Foto Pulang --}}
                <td>
                  @if($attendance->check_out_photo_path)
                    <img src="{{ asset('storage/'.$attendance->check_out_photo_path) }}"
                        alt="Foto Pulang" width="60" class="img-thumbnail rounded">
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>

              {{-- Catatan --}}
              <td>{{ $attendance->notes ?? '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center">
                @if(request('date')||request('status'))
                  Tidak ditemukan absensi sesuai filter.
                @else
                  Data absensi belum tersedia.
                @endif
              </td>
            </tr>
          @endforelse
        </tbody>

        </table>
      </div>

      <div class="d-flex justify-content-between mt-3">
        @if($attendances->onFirstPage())
          <button class="btn btn-outline-secondary btn-sm" disabled>Sebelumnya</button>
        @else
          <a href="{{ $attendances->previousPageUrl() }}" class="btn btn-outline-secondary btn-sm">Sebelumnya</a>
        @endif

        @if($attendances->hasMorePages())
          <a href="{{ $attendances->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">Selanjutnya</a>
        @else
          <button class="btn btn-outline-primary btn-sm" disabled>Selanjutnya</button>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
