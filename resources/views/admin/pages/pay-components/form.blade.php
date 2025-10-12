@extends('layouts.master')
@section('title', $model->exists ? 'Edit Component' : 'New Component')

@section('content')
@if($errors->any())
  <div class="alert alert-danger">
    <div class="fw-bold mb-1">Please fix the errors below.</div>
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
@endif
@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="post"
      action="{{ $model->exists ? route('admin.pay-components.update',$model) : route('admin.pay-components.store') }}"
      class="card">
  @csrf @if($model->exists) @method('put') @endif

  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <strong>{{ $model->exists ? 'Edit' : 'Create' }} Pay Component</strong>
      @if($model->exists)
        <span class="ms-2 small text-muted">Code <code>{{ $model->code }}</code></span>
      @endif
    </div>
    @if($model->exists)
      <div class="d-flex gap-2">
        <a href="{{ route('admin.pay-components.rates.index', $model) }}" class="btn btn-sm btn-outline-primary">
          <i class="fas fa-receipt me-1"></i> Rates
        </a>
        <a href="{{ route('admin.pay-components.rates.create', $model) }}" class="btn btn-sm btn-primary">
          + Add Rate
        </a>
      </div>
    @endif
  </div>

  <div class="card-body">
    <div class="row g-3">
      {{-- CODE + PRESETS --}}
      <div class="col-md-4">
        <label class="form-label">
          Code
          <i class="far fa-question-circle text-muted ms-1"
             data-bs-toggle="popover"
             data-bs-html="true"
             data-bs-trigger="focus"
             title="Tentang Code"
             data-bs-content="• Huruf besar, tanpa spasi (spasi → _).<br>• Harus unik.<br>• <b>BASIC</b> itu khusus: dipakai rule Gaji Pokok & prorata.">
          </i>
        </label>
        <input name="code" id="pcCode"
               class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code',$model->code) }}"
               {{ $model->exists ? 'readonly' : '' }}
               required maxlength="40" autocomplete="off">
        <div class="form-text">
          @if($model->exists) Immutable. @else Auto-uppercase, spasi → _. @endif
        </div>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror>

        {{-- Preset chips (klik untuk mengisi otomatis) --}}
        @unless($model->exists)
        <div class="small text-muted mt-2 mb-1">Contoh cepat:</div>
        <div class="d-flex flex-wrap gap-2">
          <button type="button" class="btn btn-sm btn-outline-secondary preset-chip"
                  data-code="BASIC" data-name="Gaji Pokok"
                  data-kind="earning" data-calc="fixed"
                  data-amount="" data-notes="Komponen pokok; diprorata oleh ProrataBasicRule.">
            BASIC
          </button>
          <button type="button" class="btn btn-sm btn-outline-secondary preset-chip"
                  data-code="MEAL" data-name="Tunjangan Makan"
                  data-kind="earning" data-calc="fixed"
                  data-amount="25000" data-notes="Contoh tunjangan harian/bulanan sederhana.">
            MEAL
          </button>
          <button type="button" class="btn btn-sm btn-outline-secondary preset-chip"
                  data-code="OVERTIME" data-name="Lembur"
                  data-kind="earning" data-calc="hourly"
                  data-amount="" data-notes="Dihitung per jam via aturan lembur.">
            OVERTIME
          </button>
          <button type="button" class="btn btn-sm btn-outline-secondary preset-chip"
                  data-code="BPJS_KES_EE" data-name="BPJS Kesehatan (Karyawan)"
                  data-kind="deduction" data-calc="percent"
                  data-amount="1.0" data-notes="Persentase dari base sesuai aturan payroll.">
            BPJS_KES_EE
          </button>
          <button type="button" class="btn btn-sm btn-outline-secondary preset-chip"
                  data-code="BPJS_JHT_EE" data-name="BPJS JHT (Karyawan)"
                  data-kind="deduction" data-calc="percent"
                  data-amount="2.0" data-notes="Persentase dari base sesuai aturan payroll.">
            BPJS_JHT_EE
          </button>
        </div>
        @endunless
      </div>

      <div class="col-md-5">
        <label class="form-label">Name</label>
        <input name="name" id="pcName"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name',$model->name) }}" required maxlength="120">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div class="col-md-3">
        <label class="form-label">Kind</label>
        <select name="kind" id="pcKind" class="form-select @error('kind') is-invalid @enderror" required>
          @foreach(['earning','allowance','deduction','reimbursement'] as $k)
            <option value="{{ $k }}" @selected(old('kind',$model->kind)==$k)>{{ ucfirst($k) }}</option>
          @endforeach
        </select>
        <div class="form-text">Untuk laporan & pengelompokan.</div>
        @error('kind')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      {{-- CALC TYPE + MINI GUIDE --}}
      <div class="col-md-4">
        <label class="form-label">
          Calc Type
          <i class="far fa-question-circle text-muted ms-1"
             data-bs-toggle="popover"
             data-bs-html="true"
             data-bs-trigger="focus"
             title="Penjelasan Singkat"
             data-bs-content="• <b>Fixed</b>: pakai nominal tetap (default/rate).<br>• <b>Hourly</b>: dihitung per jam (pakai rate jam).<br>• <b>Percent</b>: % dari base yang ditentukan rule (mis. BASIC).<br>• <b>Formula</b>: hasil aturan/rumus khusus.">
          </i>
        </label>
        <select name="calc_type" id="pcCalcType" class="form-select @error('calc_type') is-invalid @enderror" required>
          @foreach(['fixed','hourly','percent','formula'] as $k)
            <option value="{{ $k }}" @selected(old('calc_type',$model->calc_type)==$k)>{{ ucfirst($k) }}</option>
          @endforeach
        </select>
        <div class="form-text" id="calcHint">
          Fixed = pakai nominal default atau rate; Hourly = per jam; Percent = % dari base; Formula = hasil per aturan/rate khusus.
        </div>
        @error('calc_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div class="col-md-4">
        <label class="form-label">Default Amount</label>
        <div class="input-group">
          <input type="number" step="0.0001" min="0" name="default_amount" id="pcAmount"
                 class="form-control @error('default_amount') is-invalid @enderror"
                 value="{{ old('default_amount',$model->default_amount) }}">
          <span class="input-group-text" id="pcAmountSuffix">value</span>
        </div>
        <div class="form-text" id="amountHint">
          Dipakai jika tidak ada rate yang cocok. Untuk <em>percent</em>, isi dalam persen (mis. 10 = 10%).
        </div>
        @error('default_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div class="col-md-4">
        <label class="form-label">Notes <span class="text-muted">(optional)</span></label>
        <input name="notes" id="pcNotes"
               class="form-control @error('notes') is-invalid @enderror"
               value="{{ old('notes',$model->notes) }}" maxlength="255" placeholder="Catatan singkat...">
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div class="col-md-2">
        <label class="form-label d-block">Status</label>
        <div class="form-check form-switch mt-2">
          <input class="form-check-input" type="checkbox" id="activeSwitch" name="active" value="1" @checked(old('active', $model->active ?? true))>
          <label class="form-check-label" for="activeSwitch">Active</label>
        </div>
      </div>

      <div class="col-12 d-flex align-items-center gap-2 small text-muted">
        <span>Preview:</span>
        <span id="kindBadge" class="badge bg-secondary text-white">{{ ucfirst(old('kind',$model->kind ?? 'earning')) }}</span>
        <span id="calcBadge" class="badge bg-light text-dark">{{ ucfirst(old('calc_type',$model->calc_type ?? 'fixed')) }}</span>
        @if($model->exists)
          <span class="badge bg-info-subtle text-info">Code: {{ $model->code }}</span>
        @endif
      </div>
    </div>
  </div>

  <div class="card-footer d-flex gap-2 justify-content-between">
    <a href="{{ route('admin.pay-components.index') }}" class="btn btn-light">Back</a>
    <button class="btn btn-primary">Save</button>
  </div>
</form>
@endsection

@push('styles')
<style>
  .preset-chip { --bs-btn-padding-y:.15rem; --bs-btn-padding-x:.5rem; --bs-btn-font-size:.8rem; }
  /* pastikan preview kind selalu terbaca */
  #kindBadge { color:#fff !important; }
</style>
@endpush

@push('scripts')
<script>
  (function(){
    // Auto format CODE
    const code = document.getElementById('pcCode');
    if (code && !code.readOnly) {
      code.addEventListener('input', function(){
        this.value = this.value.toUpperCase().replace(/\s+/g,'_');
      });
    }

    const kindSel = document.getElementById('pcKind');
    const calcSel = document.getElementById('pcCalcType');
    const kindBadge = document.getElementById('kindBadge');
    const calcBadge = document.getElementById('calcBadge');
    const amtSuffix = document.getElementById('pcAmountSuffix');
    const amtHint   = document.getElementById('amountHint');

    function updateBadges(){
      const kind = kindSel.value;
      const map = {earning:'primary', allowance:'success', deduction:'danger', reimbursement:'warning'};
      kindBadge.textContent = kind.charAt(0).toUpperCase()+kind.slice(1);
      kindBadge.className = 'badge text-white bg-'+(map[kind]||'secondary');

      const calc = calcSel.value;
      calcBadge.textContent = calc.charAt(0).toUpperCase()+calc.slice(1);
      calcBadge.className = 'badge bg-light text-dark';

      if (calc === 'percent') {
        amtSuffix.textContent = '%';
        amtHint.innerHTML = 'Isi dalam persen (mis. 10 = 10%).';
      } else if (calc === 'hourly') {
        amtSuffix.textContent = 'per unit';
        amtHint.innerHTML = 'Biasanya ditentukan via rate per jam. Field ini opsional sebagai fallback.';
      } else if (calc === 'formula') {
        amtSuffix.textContent = 'value';
        amtHint.innerHTML = 'Nilai biasanya dihasilkan oleh formula/rule. Field ini opsional sebagai fallback.';
      } else {
        amtSuffix.textContent = 'value';
        amtHint.innerHTML = 'Dipakai jika tidak ada rate yang cocok.';
      }
    }
    [kindSel, calcSel].forEach(el => el && el.addEventListener('change', updateBadges));
    updateBadges();

    // Preset chips
    document.querySelectorAll('.preset-chip').forEach(btn => {
      btn.addEventListener('click', () => {
        const c = btn.dataset.code, n = btn.dataset.name;
        const k = btn.dataset.kind, t = btn.dataset.calc;
        const a = btn.dataset.amount, notes = btn.dataset.notes || '';

        if (code && !code.readOnly) code.value = c;
        document.getElementById('pcName').value = n;
        kindSel.value = k;
        calcSel.value = t;
        document.getElementById('pcAmount').value = a;
        document.getElementById('pcNotes').value = notes;

        updateBadges();
        // fokus ke Name biar terasa "terisi"
        document.getElementById('pcName').focus();
      });
    });

    // Enable Bootstrap popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(el => new bootstrap.Popover(el));
  })();
</script>
@endpush
