@extends('layouts.master')

@section('content')
{{-- Modal: Alasan Terlambat --}}
<div class="modal fade" id="lateReasonModal" tabindex="-1" aria-labelledby="lateReasonModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content rounded-4">
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title">Alasan Datang Terlambat</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <form method="POST" action="{{ route('employee.attendance.updateLateReason') }}">
        @csrf
        <div class="modal-body">
          <label class="form-label">Jelaskan alasan keterlambatan Anda</label>
          <textarea name="late_reason" class="form-control" required rows="4" placeholder="Contoh: Terjebak macet..."></textarea>
          <input type="hidden" name="attendance_id" value="{{ session('attendance_id') }}">
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Kirim</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal: Alasan Pulang Cepat --}}
<div class="modal fade" id="earlyCheckoutModal" tabindex="-1" aria-labelledby="earlyCheckoutModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content rounded-4">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Alasan Pulang Sebelum Waktu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Jelaskan alasan Anda</label>
        <textarea id="checkout_reason_modal" class="form-control" rows="4" placeholder="Contoh: Ada urusan keluarga..."></textarea>
      </div>
      <div class="modal-footer">
        <button id="submitEarlyCheckout" class="btn btn-primary">Kirim & Simpan Presensi</button>
      </div>
    </div>
  </div>
</div>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow rounded-4">
        <div class="card-header bg-primary text-white text-center">
          <h4>
            {{ !$attendance ? 'Presensi Masuk' : ($attendance && !$attendance->check_out_time ? 'Presensi Pulang' : 'Presensi Hari Ini Lengkap') }}
          </h4>
        </div>
        <div class="card-body p-4">
          @if(!$attendance || ($attendance && !$attendance->check_out_time))
          <form action="{{ route('employee.attendance.store') }}" method="POST" enctype="multipart/form-data" id="attendance-form">
            @csrf

            <div class="mb-3">
              <label class="form-label">
                {{ !$attendance ? 'Foto Selfie Masuk (Wajah Jelas)' : 'Foto Selfie Pulang (Wajah Jelas)' }}
              </label>
              <input type="file" name="photo" class="form-control" accept="image/*" capture="user" required>
            </div>

            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">
            <input type="hidden" name="checkout_reason" id="checkout_reason">

            <button type="submit" class="btn btn-success w-100">
              {{ !$attendance ? 'Simpan Presensi Masuk' : 'Simpan Presensi Pulang' }}
            </button>
          </form>
          @else
          <div class="alert alert-info text-center">
            Anda sudah melakukan presensi masuk & pulang hari ini.
          </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

{{-- JS --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
  let locationReady = false;

  // Ambil lokasi
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function (position) {
      document.getElementById('latitude').value = position.coords.latitude;
      document.getElementById('longitude').value = position.coords.longitude;
      locationReady = true;
    }, function () {
      Swal.fire({ icon: 'error', title: 'Lokasi Gagal', text: 'Aktifkan lokasi Anda.' });
    });
  }

  // Tampilkan modal telat
  @if(session('show_late_reason_modal'))
    const modalLate = new bootstrap.Modal(document.getElementById('lateReasonModal'));
    modalLate.show();
  @endif

  const form = document.getElementById('attendance-form');

  form?.addEventListener('submit', function (e) {
    const lat = document.getElementById('latitude').value;
    const lng = document.getElementById('longitude').value;

    if (!locationReady || !lat || !lng) {
      e.preventDefault();
      Swal.fire({ icon: 'warning', title: 'Lokasi belum siap', text: 'Mohon tunggu hingga lokasi terdeteksi.' });
      return;
    }

    @if($attendance && $attendance->check_in_time && !$attendance->check_out_time)
      const now = new Date();
      let shiftEnd;

      if ("{{ $shiftEndTime }}" === "00:00:00") {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        shiftEnd = new Date(`${tomorrow.toISOString().split('T')[0]}T00:00:00`);
      } else {
        shiftEnd = new Date(`{{ date('Y-m-d') }}T{{ $shiftEndTime }}`);
      }

      if (now < shiftEnd) {
        e.preventDefault();

        const modal = new bootstrap.Modal(document.getElementById('earlyCheckoutModal'));
        modal.show();

        document.getElementById('submitEarlyCheckout').onclick = () => {
          const reason = document.getElementById('checkout_reason_modal').value.trim();
          if (!reason) {
            Swal.fire({ icon: 'warning', title: 'Alasan wajib diisi!' });
            return;
          }

          document.getElementById('checkout_reason').value = reason;

          Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

          modal.hide();
          form.submit();
        };

        return;
      }
    @endif

    Swal.fire({ title: 'Memproses Presensi...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
  });
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  @if(session('error'))
    Swal.fire({
      icon: 'error',
      title: 'Presensi Gagal',
      text: "{{ session('error') }}",
      confirmButtonColor: '#d33',
      confirmButtonText: 'Tutup'
    });
  @endif
});
</script>


<script>
  const shiftEndTime = "{{ $shiftEndTime }}";
</script>
@endsection
