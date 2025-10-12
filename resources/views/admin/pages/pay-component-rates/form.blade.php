@extends('layouts.master')
@section('title', ($model->exists?'Edit':'New').' Rate · '.$payComponent->code)

@push('styles')
<style>
  :root{
    --hero-grad: linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%);
  }
  .hero-card{
    background: var(--hero-grad);
    color:#fff; border:0; border-radius:1.25rem; overflow:hidden;
  }
  .hero-card .hero-body{ padding:1.25rem 1.25rem; }
  @media (min-width:768px){ .hero-card .hero-body{ padding:1.75rem 1.75rem; } }
  .tag-soft{
    background: rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.28);
    color:#fff; padding:.35rem .6rem; border-radius:999px; font-size:.78rem; font-weight:600; white-space:nowrap;
  }
  .btn-pill{ border-radius:999px; }
  .btn-soft-white{ background:rgba(255,255,255,.15); border-color:rgba(255,255,255,.25); color:#fff; }
  .btn-soft-white:hover{ background:rgba(255,255,255,.25); color:#fff; }
</style>
@endpush

@section('content')
<div class="container py-4">

  {{-- HERO --}}
  <div class="card hero-card shadow-sm mb-3">
    <div class="hero-body d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
      <div>
        <div class="d-flex flex-wrap gap-2 mb-2">
          <span class="tag-soft">Pay Component</span>
          <span class="tag-soft">{{ $payComponent->code }}</span>
        </div>
        <h2 class="mb-1">{{ $payComponent->name }}</h2>
        <div class="small opacity-95">
          {{ $model->exists ? 'Edit Rate' : 'Tambah Rate Baru' }} ·
          Perubahan kebijakan cukup ubah **unit / rate / meta** di sini — tanpa ubah kode.
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.pay-components.rates.index',$payComponent) }}" class="btn btn-soft-white btn-pill">
          <i class="bi bi-arrow-left-short me-1"></i> Kembali ke Rates
        </a>
      </div>
    </div>
  </div>

  {{-- ERROR --}}
  @if($errors->any())
    <div class="alert alert-danger">
      <div class="fw-bold mb-1">Periksa input kamu:</div>
      <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  {{-- FORM --}}
  <form id="rateForm" method="post"
        action="{{ $model->exists? route('admin.rates.update',$model) : route('admin.pay-components.rates.store',$payComponent) }}"
        class="card shadow-sm rounded-4">
    @csrf @if($model->exists) @method('put') @endif

    <div class="card-body">
      <div class="row g-3">

        {{-- Pay Group (opsional) --}}
        @isset($payGroups)
        <div class="col-md-4">
          <label class="form-label">Pay Group (opsional)</label>
          <select name="pay_group_id" class="form-select">
            <option value="">— None —</option>
            @foreach($payGroups as $g)
              <option value="{{ $g->id }}" @selected(old('pay_group_id',$model->pay_group_id)==$g->id)>{{ $g->name }}</option>
            @endforeach
          </select>
          <div class="form-text">Biarkan kosong untuk rate default (semua group).</div>
        </div>
        @endisset

        {{-- Unit (select + custom) --}}
        @php
          $unitCurrent = old('unit', $model->unit);
          $preset = ['IDR','IDR/day','IDR/hour','IDR/minute','%','piece','trip'];
          $isCustom = $unitCurrent && !in_array($unitCurrent, $preset);
        @endphp
        <div class="col-md-4">
          <label class="form-label">Unit</label>
          <select name="unit" id="unit" class="form-select" required>
            <optgroup label="Currency">
              <option value="IDR" @selected($unitCurrent==='IDR')>IDR (fixed)</option>
            </optgroup>
            <optgroup label="Time-based">
              <option value="IDR/day"  @selected($unitCurrent==='IDR/day')>IDR / day</option>
              <option value="IDR/hour" @selected($unitCurrent==='IDR/hour')>IDR / hour</option>
              <option value="IDR/minute" @selected($unitCurrent==='IDR/minute')>IDR / minute</option>
            </optgroup>
            <optgroup label="Percent">
              <option value="%" @selected($unitCurrent==='%')>% (percent)</option>
            </optgroup>
            <optgroup label="Other">
              <option value="piece" @selected($unitCurrent==='piece')>per piece</option>
              <option value="trip"  @selected($unitCurrent==='trip')>per trip</option>
              <option value="custom" @selected($isCustom)>Custom…</option>
            </optgroup>
          </select>
          <input name="unit_custom" id="unit_custom"
                 value="{{ $isCustom ? $unitCurrent : '' }}"
                 class="form-control mt-2 {{ $isCustom? '' : 'd-none' }}"
                 placeholder="Contoh: IDR/km, km, points">
          <div class="form-text">Gunakan <b>%</b> untuk persentase; engine mendeteksi tipe otomatis dari unit.</div>
        </div>

        {{-- Rate --}}
        <div class="col-md-4">
          <label class="form-label">Rate</label>
          <div class="input-group">
            <input type="number" step="0.0001" min="0" name="rate" value="{{ old('rate',$model->rate) }}" class="form-control" required>
            <span id="rateSuffix" class="input-group-text">/ unit</span>
          </div>
          <div class="form-text">Contoh: 1 → 1% bila unit = %, 25000 → IDR 25.000.</div>
        </div>

        {{-- Formula (opsional) --}}
        <div class="col-12">
          <label class="form-label">Formula <span class="text-muted">(opsional)</span></label>
          <input name="formula" value="{{ old('formula',$model->formula) }}" class="form-control" placeholder="Contoh: BASIC * 1.5 atau OT_HOURS * 20000">
          <div class="form-text">Gunakan <code>COMPONENT_CODE</code> (mis. BASIC). Kosongkan jika rate absolut.</div>
        </div>

        {{-- Meta khusus %: basis & cap --}}
        @php
          $meta = is_array($model->meta ?? null) ? $model->meta : (json_decode($model->meta ?? '[]', true) ?: []);
          $basis = old('meta.basis', $meta['basis'] ?? null);
          $cap   = old('meta.cap',   $meta['cap']   ?? null);
        @endphp
        <div class="col-md-4" id="percentBasisWrap" style="{{ $unitCurrent==='%' ? '' : 'display:none' }}">
          <label class="form-label">Basis (opsional, untuk %)</label>
          <select name="meta[basis]" id="percentBasis" class="form-select">
            <option value="">— Default (engine) —</option>
            <option value="bpjs_base" @selected($basis==='bpjs_base')>bpjs_base</option>
            <option value="basic"     @selected($basis==='basic')>basic</option>
            <option value="gross"     @selected($basis==='gross')>gross</option>
            <option value="net"       @selected($basis==='net')>net</option>
          </select>
          <div class="form-text">Kalau kosong, engine pakai basis default (mis. bpjs_base untuk BPJS).</div>
        </div>

        <div class="col-md-4" id="percentCapWrap" style="{{ $unitCurrent==='%' ? '' : 'display:none' }}">
          <label class="form-label">Cap (opsional, untuk %)</label>
          <div class="input-group">
            <span class="input-group-text">IDR</span>
            <input name="meta[cap]" type="number" min="0" step="1" class="form-control" value="{{ $cap }}">
          </div>
          <div class="form-text">Batas maksimum dasar perhitungan (contoh: JP max 12 juta).</div>
        </div>

        {{-- Periode --}}
        <div class="col-md-6">
          <label class="form-label">Effective Start</label>
          <input id="effStart" type="date" name="effective_start" value="{{ old('effective_start', optional($model->effective_start)->toDateString()) }}" class="form-control" required>
        </div>

        <div class="col-md-6">
          <div class="d-flex align-items-end gap-2">
            <div class="flex-grow-1">
              <label class="form-label">Effective End</label>
              <input id="effEnd" type="date" name="effective_end" value="{{ old('effective_end', optional($model->effective_end)->toDateString()) }}" class="form-control" placeholder="(kosongkan untuk ∞)">
            </div>
            <div class="form-check mb-2">
              @php $hasEnd = old('effective_end', optional($model->effective_end)->toDateString()); @endphp
              <input class="form-check-input" type="checkbox" id="noEnd" {{ $hasEnd ? '' : 'checked' }}>
              <label class="form-check-label" for="noEnd">No end</label>
            </div>
          </div>
          <div id="dateAlert" class="small text-danger d-none mt-1">End date harus ≥ Start date.</div>
        </div>

      </div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center">
      <a href="{{ route('admin.pay-components.rates.index',$payComponent) }}" class="btn btn-light btn-pill">
        <i class="bi bi-arrow-left-short me-1"></i> Back
      </a>
      <button id="btnSave" type="button" class="btn btn-primary btn-pill">
        <i class="bi bi-save me-1"></i> Save
      </button>
    </div>
  </form>
</div>
@endsection

@push('scripts')
{{-- SweetAlert2 (fallback CDN) --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function(){
  const form       = document.getElementById('rateForm');
  const unit       = document.getElementById('unit');
  const unitCustom = document.getElementById('unit_custom');
  const rateSuffix = document.getElementById('rateSuffix');
  const basisWrap  = document.getElementById('percentBasisWrap');
  const capWrap    = document.getElementById('percentCapWrap');
  const start      = document.getElementById('effStart');
  const end        = document.getElementById('effEnd');
  const noEnd      = document.getElementById('noEnd');
  const alertEl    = document.getElementById('dateAlert');
  const btnSave    = document.getElementById('btnSave');

  function currentUnit(){
    return unit.value === 'custom' ? (unitCustom.value || '').trim() : unit.value;
  }

  function updateSuffix(){
    const u = currentUnit();
    let label = '/ unit';
    if (u === '%') label = '%';
    else if (u === 'IDR') label = 'IDR';
    else if (u.includes('/day')) label = '/ day';
    else if (u.includes('/hour')) label = '/ hour';
    else if (u.includes('/minute')) label = '/ minute';
    else if (u.startsWith('IDR')) label = u.replace('IDR','IDR ');
    else if (u) label = u;
    rateSuffix.textContent = label;

    const isPercent = (u === '%');
    basisWrap.style.display = isPercent ? '' : 'none';
    capWrap.style.display   = isPercent ? '' : 'none';
  }

  function toggleCustom(){
    const show = unit.value === 'custom';
    unitCustom.classList.toggle('d-none', !show);
    if (!show) unitCustom.value = '';
    updateSuffix();
  }

  function toggleEnd(){
    const dis = noEnd.checked;
    end.disabled = dis;
    if (dis) { end.value = ''; alertEl.classList.add('d-none'); }
  }

  function validateRange(){
    if (!end.value || !start.value) { alertEl.classList.add('d-none'); return true; }
    const ok = end.value >= start.value;
    alertEl.classList.toggle('d-none', ok);
    return ok;
  }

  unit.addEventListener('change', toggleCustom);
  unitCustom.addEventListener('input', updateSuffix);
  noEnd.addEventListener('change', toggleEnd);
  start.addEventListener('change', validateRange);
  end.addEventListener('change', validateRange);

  // init
  toggleCustom(); toggleEnd(); updateSuffix();

  // SweetAlert confirm save
  btnSave.addEventListener('click', function(){
    if (!validateRange()) return;
    const unitText = currentUnit() || '(unit tidak diisi)';
    const rateVal  = (form.querySelector('input[name="rate"]').value || '0');

    Swal.fire({
      title: 'Simpan Rate?',
      html: `
        <div class="text-start">
          <div><b>Component:</b> {{ addslashes($payComponent->code) }} — {{ addslashes($payComponent->name) }}</div>
          <div><b>Unit:</b> ${unitText}</div>
          <div><b>Rate:</b> ${rateVal}</div>
          <div class="mt-1 small text-muted">Kamu bisa ubah kebijakan cukup lewat data ini (unit/rate/meta).</div>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya, Simpan',
      cancelButtonText: 'Batal',
      customClass: { confirmButton: 'btn btn-primary btn-pill', cancelButton: 'btn btn-outline-secondary btn-pill' },
      buttonsStyling: false
    }).then((res)=>{
      if(res.isConfirmed){ form.submit(); }
    });
  });
})();
</script>

@if(session('success'))
<script>
  Swal.fire({ toast:true, position:'top-end', icon:'success', title:@json(session('success')), timer:2200, showConfirmButton:false });
</script>
@endif
@if(session('error'))
<script>
  Swal.fire({ toast:true, position:'top-end', icon:'error', title:@json(session('error')), timer:2600, showConfirmButton:false });
</script>
@endif
@endpush
