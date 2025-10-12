@extends('layouts.master')

@section('title', 'Notifikasi Saya')

@section('content')
<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">🔔 Notifikasi Saya</h1>

  <div class="card shadow">
    <div class="card-body">

      {{-- Tombol tandai semua --}}
      <form action="{{ route('notifications.markAllAsRead') }}" method="POST" class="mb-3">
        @csrf
        @method('PATCH')
        <button class="btn btn-sm btn-success"
                onclick="return confirm('Tandai semua notifikasi sebagai sudah dibaca?')">
          Tandai Semua Sudah Dibaca
        </button>
      </form>

      @if ($notifications->isEmpty())
        <div class="alert alert-info mb-0">Belum ada notifikasi.</div>
      @else
        <div class="table-responsive">
          <table class="table table-bordered table-hover">
            <thead class="thead-light">
              <tr>
                <th>Jenis</th>
                <th>Waktu</th>
                <th>Judul</th>
                <th>Pesan</th>
                <th>Oleh</th> {{-- NEW --}}
                <th style="width: 140px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($notifications as $notif)
                @php
                  // --- NEW: Normalisasi meta & ambil nama pelaku ---
                  // Support beberapa kemungkinan penyimpanan payload (kolom array/JSON/string).
                  $meta = $notif->meta ?? $notif->data ?? null;
                  if (is_string($meta)) {
                    $decoded = json_decode($meta, true);
                    $meta = is_array($decoded) ? $decoded : [];
                  } elseif (!is_array($meta)) {
                    $meta = [];
                  }

                  // Cari "siapa" dari berbagai kunci yang umum dipakai
                  $byName = $notif->by_name
                           ?? $notif->approved_by_name
                           ?? $notif->rejected_by_name
                           ?? data_get($meta, 'by_name')
                           ?? data_get($meta, 'approved_by_name')
                           ?? data_get($meta, 'rejected_by_name')
                           ?? data_get($meta, 'actor_name')
                           ?? null;

                  // (opsional) ambil role juga kalau ada
                  $byRole = $notif->by_role
                           ?? data_get($meta, 'by_role')
                           ?? data_get($meta, 'actor_role')
                           ?? null;

                  // Tipe yang relevan (supaya "Oleh" hanya muncul bermakna untuk approved/rejected)
                  $isDecision = in_array($notif->type, [
                    'attendance_request_approved','attendance_request_rejected',
                    'overtime_request_approved','overtime_request_rejected',
                    'leave_request_approved','leave_request_rejected',
                  ], true);
                @endphp

                <tr class="{{ $notif->is_read ? 'table-light' : '' }}">
                  <td>
                    @switch($notif->type)
                      @case('late')                       <span class="badge badge-warning">Terlambat</span> @break
                      @case('early_leave')                <span class="badge badge-info">Pulang Awal</span> @break
                      @case('no_check_in')                <span class="badge badge-danger">Tidak Check-In</span> @break
                      @case('attendance_request')         <span class="badge badge-secondary">Pengajuan Presensi</span> @break
                      @case('attendance_request_approved')<span class="badge badge-success">Presensi Disetujui</span> @break
                      @case('attendance_request_rejected')<span class="badge badge-danger">Presensi Ditolak</span> @break
                      @case('overtime_request')           <span class="badge badge-primary">Lembur Diajukan</span> @break
                      @case('overtime_request_approved')  <span class="badge badge-success">Lembur Disetujui</span> @break
                      @case('overtime_request_rejected')  <span class="badge badge-danger">Lembur Ditolak</span> @break
                      @default                            <span class="badge badge-light">{{ $notif->type }}</span>
                    @endswitch
                  </td>

                  <td>{{ $notif->created_at->format('d-m-Y H:i') }}</td>
                  <td>{{ $notif->title }}</td>
                  <td class="small mb-0">
                    {{ $notif->message }}
                  </td>

                  {{-- NEW: Kolom "Oleh" --}}
                  <td class="small">
                    @if ($isDecision)
                      @if ($byName)
                        <span class="d-inline-flex align-items-center">
                          <i class="fas fa-user-check mr-1 text-muted"></i>
                          <strong>{{ $byName }}</strong>
                          @if ($byRole)
                            <span class="badge badge-light ml-2">{{ $byRole }}</span>
                          @endif
                        </span>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>

                  <td>
                    @if (!$notif->is_read)
                      <form action="{{ route('notifications.markAsRead', $notif->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-sm btn-primary">Tandai Dibaca</button>
                      </form>
                    @else
                      <button class="btn btn-sm btn-secondary" disabled>Sudah</button>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-center">
          {{ $notifications->links() }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
