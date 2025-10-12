@extends('layouts.master')
@section('title', ($model->exists ? 'Edit' : 'Link').' Component · '.$payGroup->name)

@push('styles')
<style>
  :root{ --soft-bg: rgba(78,115,223,.10); --soft-bd: rgba(78,115,223,.25); }
  .card-rounded{ border-radius: 1rem; }
  .shadow-soft{ box-shadow: 0 14px 30px -14px rgba(0,0,0,.25); }
  .btn-soft-primary{ background: var(--soft-bg); border-color: var(--soft-bd); color:#4e73df; }
  .btn-soft-primary:hover{ background: rgba(78,115,223,.14); }
  .rounded-pill{ border-radius: 999px!important; }
  .form-section + .form-section{ border-top: 1px dashed #e9ecef; margin-top: .75rem; padding-top: .75rem; }
  .custom-switch .custom-control-label::before{ top:.15rem; }
  .custom-switch .custom-control-label::after{ top:.35rem; }
</style>
@endpush

@section('content')
@if($errors->any())
  <div class="alert alert-danger">
    <div class="font-weight-bold mb-1">Periksa input:</div>
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
@endif

<form id="pgc-form" method="post"
      action="{{ $model->exists
                  ? route('admin.components.update', $model)
                  : route('admin.pay-groups.components.store', $payGroup) }}"
      class="card card-rounded shadow-soft">
  @csrf
  @if($model->exists) @method('PUT') @endif

  <div class="card-header d-flex align-items-center justify-content-between">
    <div>
      <div class="small text-muted mb-1">Pay Group</div>
      <strong class="h5 mb-0">{{ $payGroup->name }}</strong>
    </div>
    <span class="badge badge-light text-muted rounded-pill px-3 py-2">
      {{ $model->exists ? 'Edit Link' : 'Link New Component' }}
    </span>
  </div>

  <div class="card-body">
    <div class="row">
      <div class="col-md-7 form-section">
        <label class="mb-1">Component</label>
        <select name="pay_component_id" class="custom-select" required>
          <option value="" disabled {{ old('pay_component_id',$model->pay_component_id) ? '' : 'selected' }}>Pilih komponen…</option>
          @foreach($components as $c)
            <option value="{{ $c->id }}" @selected(old('pay_component_id',$model->pay_component_id)==$c->id)>
              {{ $c->name }} ({{ $c->code }})
            </option>
          @endforeach
        </select>
        <small class="form-text text-muted">Pilih komponen yang akan diaktifkan pada pay group ini.</small>
      </div>

      <div class="col-md-2 form-section">
        <label class="mb-1">Sequence</label>
        <input type="number" name="sequence" class="form-control"
               value="{{ old('sequence', $model->sequence) }}" placeholder="0" min="0">
        <small class="form-text text-muted">Urutan perhitungan/tampilan.</small>
      </div>

      <div class="col-md-3 form-section">
        <div class="custom-control custom-switch mb-2">
          <input type="checkbox" class="custom-control-input" id="mandatorySwitch" name="mandatory" value="1"
                 @checked(old('mandatory', $model->mandatory ?? true))>
          <label class="custom-control-label" for="mandatorySwitch">Mandatory</label>
        </div>
        <div class="custom-control custom-switch">
          <input type="checkbox" class="custom-control-input" id="activeSwitch" name="active" value="1"
                 @checked(old('active', $model->active ?? true))>
          <label class="custom-control-label" for="activeSwitch">Active</label>
        </div>
      </div>

      <div class="col-12 form-section">
        <label class="mb-1">Notes</label>
        <textarea name="notes" rows="2" class="form-control" placeholder="Catatan internal (opsional)">{{ old('notes', $model->notes) }}</textarea>
      </div>
    </div>
  </div>

  <div class="card-footer bg-white d-flex flex-wrap align-items-center">
    <a href="{{ route('admin.pay-groups.components.index', $payGroup) }}"
       class="btn btn-outline-secondary rounded-pill mr-2 mb-2">
      <i class="fas fa-arrow-left mr-1"></i> Back
    </a>

    <button type="submit" class="btn btn-primary rounded-pill mb-2" id="btn-save">
      <i class="fas fa-save mr-1"></i> Save
    </button>

    <button type="button" class="btn btn-soft-primary rounded-pill ml-auto mb-2" id="btn-reset">
      <i class="fas fa-undo mr-1"></i> Reset
    </button>
  </div>
</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function(){
  // Konfirmasi submit
  const form = document.getElementById('pgc-form');
  const btnSave = document.getElementById('btn-save');
  const btnReset = document.getElementById('btn-reset');

  // Toast helper
  const Toast = Swal.mixin({ toast:true, position:'top-end', timer:2500, showConfirmButton:false, timerProgressBar:true });

  btnReset?.addEventListener('click', function(){
    Swal.fire({
      icon: 'question',
      title: 'Reset formulir?',
      text: 'Semua isian akan dikembalikan.',
      showCancelButton: true,
      confirmButtonText: 'Ya, reset',
      cancelButtonText: 'Batal',
      reverseButtons: true
    }).then(r => { if(r.isConfirmed) form.reset(); });
  });

  form.addEventListener('submit', function(e){
    e.preventDefault();
    const sel = form.querySelector('select[name="pay_component_id"]');
    const compText = sel && sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : '(tanpa komponen)';
    const mode = {{ $model->exists ? json_encode('update') : json_encode('create') }};

    Swal.fire({
      icon: 'question',
      title: mode === 'update' ? 'Simpan perubahan link?' : 'Link komponen ke pay group?',
      html: `<div class="text-left small">
               <div><b>Group:</b> {{ addslashes($payGroup->name) }}</div>
               <div><b>Component:</b> ${compText}</div>
             </div>`,
      showCancelButton: true,
      confirmButtonText: 'Ya, simpan',
      cancelButtonText: 'Batal',
      reverseButtons: true,
      customClass: { confirmButton: 'rounded-pill', cancelButton: 'rounded-pill' }
    }).then(res => {
      if(!res.isConfirmed) return;
      // Optional: disable button agar anti-double click
      btnSave.disabled = true;
      btnSave.innerHTML = '<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span> Saving...';
      form.submit();
    });
  });

  // Ctrl/Cmd + S untuk save cepat
  window.addEventListener('keydown', function(e){
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
      e.preventDefault();
      document.getElementById('btn-save')?.click();
    }
  });

  // Flash success (jika ada di session)
  @if(session('success'))
    Toast.fire({ icon:'success', title: @json(session('success')) });
  @endif
})();
</script>
@endpush
