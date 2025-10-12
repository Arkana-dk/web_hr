@extends('layouts.master')

@section('title', 'Buat Pay Run')

@section('content')
<div class="container py-4">
  <div class="card shadow rounded-4">
    <div class="card-header bg-primary text-white rounded-top-4 d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Buat Pay Run</h5>
      <a href="{{ route('admin.payruns.index') }}" class="btn btn-sm btn-light">Kembali</a>
    </div>

    <div class="card-body">
      @if ($errors->any())
        <div class="alert alert-danger">
          <strong>Periksa input berikut:</strong>
          <ul class="mb-0">@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
        </div>
      @endif

      <form id="payrunForm" method="POST" action="{{ route('admin.payruns.store') }}">
        @csrf

        <div class="mb-3">
          <label class="form-label">Pay Group</label>
          <select name="pay_group_id" id="payGroup" class="form-select" required>
            <option value="">-- pilih --</option>
            @foreach($groups as $g)
              <option value="{{ $g->id }}" @selected(old('pay_group_id') == $g->id)>
                {{ $g->code }} — {{ $g->name }}
              </option>
            @endforeach
          </select>
          @error('pay_group_id') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" id="startDate" class="form-control"
                   value="{{ old('start_date') }}" required>
            @error('start_date') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" id="endDate" class="form-control"
                   value="{{ old('end_date') }}" required>
            <div id="rangeAlert" class="small text-danger d-none mt-1">End date harus ≥ Start date.</div>
            @error('end_date') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>
        </div>

        {{-- Quick presets --}}
        <div class="mt-3">
          <div class="small text-muted mb-1">Quick presets:</div>
          <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-preset="this-month">This Month</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-preset="last-month">Last Month</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-preset="last-14">Last 14 Days</button>
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <a href="{{ route('admin.payruns.index') }}" class="btn btn-outline-secondary">Batal</a>
          <button type="submit" class="btn btn-primary" id="submitBtn">Buat Draft</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (function(){
    const start = document.getElementById('startDate');
    const end = document.getElementById('endDate');
    const alertEl = document.getElementById('rangeAlert');
    const form = document.getElementById('payrunForm');
    const submitBtn = document.getElementById('submitBtn');

    // Helpers
    function pad(n){ return (n<10 ? '0' : '') + n; }
    function ymd(d){ return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()); }

    function setPreset(which){
      const today = new Date();
      let s, e;

      if (which === 'this-month') {
        s = new Date(today.getFullYear(), today.getMonth(), 1);
        e = new Date(today.getFullYear(), today.getMonth()+1, 0);
      } else if (which === 'last-month') {
        s = new Date(today.getFullYear(), today.getMonth()-1, 1);
        e = new Date(today.getFullYear(), today.getMonth(), 0);
      } else if (which === 'last-14') {
        e = new Date(today.getFullYear(), today.getMonth(), today.getDate());
        s = new Date(e); s.setDate(e.getDate() - 13);
      }
      if (s && e) { start.value = ymd(s); end.value = ymd(e); end.min = start.value; validateRange(); }
    }

    // Validate end >= start
    function validateRange(){
      if (!start.value || !end.value) { alertEl.classList.add('d-none'); return true; }
      const ok = end.value >= start.value;
      alertEl.classList.toggle('d-none', ok);
      return ok;
    }

    document.querySelectorAll('[data-preset]').forEach(btn => {
      btn.addEventListener('click', () => setPreset(btn.getAttribute('data-preset')));
    });

    start.addEventListener('change', () => {
      if (end.value && end.value < start.value) end.value = start.value;
      end.min = start.value || '';
      validateRange();
    });
    end.addEventListener('change', validateRange);

    form.addEventListener('submit', (e) => {
      if (!validateRange()) { e.preventDefault(); return; }
      submitBtn.disabled = true;
    });
  })();
</script>
@endpush
