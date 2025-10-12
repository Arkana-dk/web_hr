@extends('layouts.master')
@section('title','Ajukan Cuti')
@include('components.leave.styles-soft')

@section('content')
<div class="container-fluid">
  {{-- HERO --}}
  <div class="card hero-card mb-3">
    <div class="hero-body d-flex justify-content-between align-items-center">
      <div>
        <div class="h3 fw-bold mb-0">Ajukan Cuti</div>
        <div class="opacity-75">Form pengajuan cuti karyawan</div>
      </div>
    </div>
  </div>

  {{-- FORM --}}
  <div class="card soft">
    <div class="card-body">
      <form method="POST" action="{{ route('employee.leave.store') }}" enctype="multipart/form-data" id="leave-form">
        @csrf

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Jenis Cuti</label>
            <select name="leave_type_id" id="leave_type_id" class="form-select" required>
              <option value="">Pilih jenis cuti</option>
              @foreach($leaveTypes as $lt)
                <option value="{{ $lt->id }}"
                        data-requires-attachment="{{ $lt->requires_attachment ? 1 : 0 }}"
                        data-remaining="{{ (float)($balances[$lt->id]['remaining'] ?? 0) }}"
                        data-used="{{ (float)($balances[$lt->id]['used'] ?? 0) }}"
                        @selected(old('leave_type_id') == $lt->id)>
                  {{ $lt->name }}
                </option>
              @endforeach
            </select>
            @error('leave_type_id') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-3">
            <label class="form-label">Tanggal Mulai</label>
            <input type="date" name="start_date" value="{{ old('start_date') }}" class="form-control" required>
            @error('start_date') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-3">
            <label class="form-label">Tanggal Selesai</label>
            <input type="date" name="end_date" value="{{ old('end_date') }}" class="form-control" required>
            @error('end_date') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>

          <div class="col-12">
            <label class="form-label">Alasan</label>
            <input type="text" name="reason" value="{{ old('reason') }}" class="form-control" maxlength="255" placeholder="Opsional">
            @error('reason') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>

          <div class="col-12" id="attachment-wrap" style="display:none;">
            <label class="form-label">Lampiran (wajib untuk jenis tertentu)</label>
            <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            @error('attachment') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>
        </div>

        {{-- Info Kuota --}}
        <div class="row g-3 mt-2">
          <div class="col-md-4">
            <div class="card stat-card">
              <div class="stat-body">
                <div>
                  <div class="stat-label">Sudah Dipakai (thn ini)</div>
                  <div class="stat-value fs-5" id="used-label">0</div>
                </div>
                <i class="fas fa-hourglass-half text-secondary fs-4"></i>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card stat-card">
              <div class="stat-body">
                <div>
                  <div class="stat-label">Sisa Kuota</div>
                  <div class="stat-value fs-5" id="remaining-label">0</div>
                </div>
                <i class="fas fa-wallet text-secondary fs-4"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-3 d-flex gap-2">
          <button class="btn btn-primary btn-pill"><i class="fas fa-paper-plane"></i> Kirim</button>
          <a href="{{ route('employee.leave.history') }}" class="btn btn-light btn-pill">Batal</a>
        </div>

        @error('leave') <div class="text-danger mt-2">{{ $message }}</div> @enderror
        @if(session('success')) <div class="text-success mt-2">{{ session('success') }}</div> @endif
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const select = document.getElementById('leave_type_id');
  const wrap   = document.getElementById('attachment-wrap');
  const usedEl = document.getElementById('used-label');
  const remEl  = document.getElementById('remaining-label');

  function refreshInfo() {
    const opt = select.options[select.selectedIndex];
    if(!opt) return;
    const reqAtt = opt.getAttribute('data-requires-attachment') === '1';
    wrap.style.display = reqAtt ? 'block' : 'none';

    const used = opt.getAttribute('data-used') || 0;
    const rem  = opt.getAttribute('data-remaining') || 0;
    usedEl.textContent = used;
    remEl.textContent  = rem;
  }

  select.addEventListener('change', refreshInfo);
  refreshInfo();
})();
</script>
@endpush
