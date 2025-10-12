@extends('layouts.master')

@section('title', 'Daftar Notifikasi')

@section('content')
<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">📋 Daftar Notifikasi</h1>

  {{-- FILTERS + ACTIONS --}}
  <div class="card shadow mb-3">
    <div class="card-body d-flex align-items-end flex-wrap">

      {{-- Form FILTER (GET) --}}
      <form class="form-inline mr-auto" method="GET" action="{{ route('admin.notifications.index') }}">
        <div class="form-group mr-2 mb-2">
          <label class="mr-2">Jenis</label>
          <select name="type" class="form-control">
            <option value="">Semua</option>
            <option value="late" {{ request('type')=='late'?'selected':'' }}>Terlambat</option>
            <option value="early_leave" {{ request('type')=='early_leave'?'selected':'' }}>Pulang Awal</option>
            <option value="no_check_in" {{ request('type')=='no_check_in'?'selected':'' }}>Tidak Check-In</option>
            <option value="no_check_out" {{ request('type')=='no_check_out'?'selected':'' }}>Tidak Check-Out</option>

            <option value="attendance_request" {{ request('type')=='attendance_request'?'selected':'' }}>Pengajuan Presensi</option>
            <option value="attendance_request_approved" {{ request('type')=='attendance_request_approved'?'selected':'' }}>Presensi Disetujui</option>
            <option value="attendance_request_rejected" {{ request('type')=='attendance_request_rejected'?'selected':'' }}>Presensi Ditolak</option>
            <option value="attendance_reason_submitted" {{ request('type')=='attendance_reason_submitted'?'selected':'' }}>Alasan Dikirim</option>
            <option value="attendance_reason_approved" {{ request('type')=='attendance_reason_approved'?'selected':'' }}>Alasan Diterima</option>
            <option value="attendance_reason_rejected" {{ request('type')=='attendance_reason_rejected'?'selected':'' }}>Alasan Ditolak</option>

            <option value="overtime_request" {{ request('type')=='overtime_request'?'selected':'' }}>Lembur Diajukan</option>
            <option value="overtime_request_approved" {{ request('type')=='overtime_request_approved'?'selected':'' }}>Lembur Disetujui</option>
            <option value="overtime_request_rejected" {{ request('type')=='overtime_request_rejected'?'selected':'' }}>Lembur Ditolak</option>

            <option value="shift_change_request" {{ request('type')=='shift_change_request'?'selected':'' }}>Pindah Shift Diajukan</option>
            <option value="shift_change_approved" {{ request('type')=='shift_change_approved'?'selected':'' }}>Pindah Shift Disetujui</option>
            <option value="shift_change_rejected" {{ request('type')=='shift_change_rejected'?'selected':'' }}>Pindah Shift Ditolak</option>
          </select>
        </div>

        <div class="form-group mr-2 mb-2">
          <label class="mr-2">Status Baca</label>
          <select name="read" class="form-control">
            <option value=""  {{ request()->has('read') && request('read')==='' ? 'selected' : '' }}>Semua</option>
            <option value="0" {{ request()->has('read') && request('read')==='0' ? 'selected' : '' }}>Belum dibaca</option>
            <option value="1" {{ request()->has('read') && request('read')==='1' ? 'selected' : '' }}>Sudah dibaca</option>
          </select>
        </div>

        <div class="form-group mr-2 mb-2">
          <label class="mr-2">Tanggal</label>
          <input type="date" name="date"
                 value="{{ request('date') ? \Carbon\Carbon::parse(request('date'))->format('Y-m-d') : '' }}"
                 class="form-control">
        </div>

        <button class="btn btn-primary mb-2 mr-2">Filter</button>
        <a href="{{ route('admin.notifications.index') }}" class="btn btn-light mb-2">Reset</a>
      </form>

      {{-- Form MARK ALL READ (PATCH) — DIPISAH dari form GET --}}
      <form action="{{ route('admin.notifications.markAllAsRead') }}" method="POST" class="mb-2">
        @csrf
        @method('PATCH')
        <button class="btn btn-success"
                onclick="return confirm('Tandai semua notifikasi sebagai sudah dibaca?')">
          Tandai Semua Sudah Dibaca
        </button>
      </form>

    </div>
  </div>

  <div class="card shadow">
    <div class="card-body">
      @if ($notifications->isEmpty())
        <div class="alert alert-info">Belum ada notifikasi.</div>
      @else
        <div class="table-responsive">
          <table class="table table-bordered table-hover">
            <thead class="thead-light">
              <tr>
                <th>Jenis</th>
                <th>Waktu</th>
                <th>Karyawan</th>
                <th>Judul</th>
                <th>Pesan</th>
                <th>Oleh</th> {{-- NEW: kolom approve/reject by --}}
                <th>Status</th>
                <th style="width: 150px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($notifications as $notif)
                @php
                  // --- Extract meta/data secara aman ---
                  $meta = $notif->meta ?? $notif->data ?? null;
                  if (is_string($meta)) {
                      $decoded = json_decode($meta, true);
                      $meta = is_array($decoded) ? $decoded : [];
                  } elseif (!is_array($meta)) {
                      $meta = [];
                  }

                  // Jenis yang merupakan keputusan (punya "Oleh")
                  $isDecision = in_array($notif->type, [
                      'attendance_request_approved','attendance_request_rejected',
                      'overtime_request_approved','overtime_request_rejected',
                      'leave_request_approved','leave_request_rejected',
                      'shift_change_approved','shift_change_rejected',
                  ], true);

                  // Ambil nama & role pelaku dari berbagai kemungkinan kunci
                  $byName = $notif->by_name
                           ?? data_get($meta, 'by_name')
                           ?? data_get($meta, 'approved_by_name')
                           ?? data_get($meta, 'rejected_by_name')
                           ?? data_get($meta, 'actor_name');

                  $byRole = $notif->by_role
                           ?? data_get($meta, 'by_role')
                           ?? data_get($meta, 'actor_role');

                  // Fallback terakhir: tampilkan user id jika ada
                  $byId   = $notif->by_user_id ?? data_get($meta, 'by_id');
                @endphp

                <tr class="{{ $notif->is_read ? 'table-light' : '' }}">
                  <td>
                    @switch($notif->type)
                      @case('late')  <span class="badge badge-warning">Terlambat</span> @break
                      @case('early_leave') <span class="badge badge-info">Pulang Awal</span> @break
                      @case('no_check_in') <span class="badge badge-danger">Tidak Check-In</span> @break
                      @case('no_check_out') <span class="badge badge-danger">Tidak Check-Out</span> @break

                      @case('attendance_request') <span class="badge badge-secondary">Pengajuan Presensi</span> @break
                      @case('attendance_request_approved') <span class="badge badge-success">Presensi Disetujui</span> @break
                      @case('attendance_request_rejected') <span class="badge badge-danger">Presensi Ditolak</span> @break
                      @case('attendance_reason_submitted') <span class="badge badge-info">Alasan Dikirim</span> @break
                      @case('attendance_reason_approved') <span class="badge badge-success">Alasan Diterima</span> @break
                      @case('attendance_reason_rejected') <span class="badge badge-danger">Alasan Ditolak</span> @break

                      @case('overtime_request') <span class="badge badge-primary">Lembur Diajukan</span> @break
                      @case('overtime_request_approved') <span class="badge badge-success">Lembur Disetujui</span> @break
                      @case('overtime_request_rejected') <span class="badge badge-danger">Lembur Ditolak</span> @break

                      @case('shift_change_request') <span class="badge badge-primary">Pindah Shift Diajukan</span> @break
                      @case('shift_change_approved') <span class="badge badge-success">Pindah Shift Disetujui</span> @break
                      @case('shift_change_rejected') <span class="badge badge-danger">Pindah Shift Ditolak</span> @break

                      @default
                        <span class="badge badge-light">{{ $notif->type }}</span>
                    @endswitch
                  </td>

                  <td>{{ $notif->created_at->format('d-m-Y H:i') }}</td>
                  <td>{{ $notif->employee->name ?? '-' }}</td>
                  <td>{{ $notif->title }}</td>
                  <td class="small">{{ $notif->message }}</td>

                  {{-- NEW: kolom "Oleh" --}}
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
                      @elseif ($byId)
                        <span class="text-muted">User #{{ $byId }}</span>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>

                  <td>
                    @if ($notif->is_read)
                      <span class="badge badge-success">Sudah dibaca</span>
                    @else
                      <span class="badge badge-danger">Belum dibaca</span>
                    @endif
                  </td>

                  <td>
                    @if (!$notif->is_read)
                      <form action="{{ route('admin.notifications.markAsRead', $notif->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-sm btn-primary">Tandai Sudah Dibaca</button>
                      </form>
                    @else
                      <button class="btn btn-sm btn-secondary" disabled>✔</button>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
          {{ $notifications->links() }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
