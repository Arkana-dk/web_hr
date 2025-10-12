@csrf
@php
  $rules          = (array) ($policy->rules ?? []);
  $annualQuota    = old('annual_quota', $rules['annual_quota'] ?? 12);
  $isProrated     = old('is_prorated',  $rules['is_prorated'] ?? false);
  $allowCarryOver = old('allow_carry_over', $rules['allow_carry_over'] ?? false);
  $appliesTo      = old('applies_to',  $rules['applies_to'] ?? 'all');
  $appliesValue   = old('applies_value', $rules['applies_value'] ?? '');
  $effStart       = old('effective_start', optional($policy->effective_start ?? null)->format('Y-m-d'));
  $effEnd         = old('effective_end',   optional($policy->effective_end ?? null)->format('Y-m-d'));
@endphp

<div class="mb-3">
  <label class="form-label">Nama Policy</label>
  <input type="text" name="name" class="form-control"
         value="{{ old('name', $policy->name ?? '') }}" required>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label">Tipe Cuti</label>
    <select name="leave_type_id" class="form-control" required>
      <option value="">Pilih</option>
      @foreach($leaveTypes as $lt)
        <option value="{{ $lt->id }}"
          @selected(old('leave_type_id', $policy->leave_type_id ?? '') == $lt->id)>
          {{ $lt->name ?? ('ID '.$lt->id) }}
        </option>
      @endforeach
    </select>
  </div>

  <div class="col-md-6">
    <label class="form-label">Kuota Tahunan</label>
    <input type="number" step="0.5" name="annual_quota" class="form-control"
           value="{{ $annualQuota }}" required>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-md-6">
    <label class="form-label">Mulai Berlaku</label>
    <input type="date" name="effective_start" class="form-control"
           value="{{ $effStart }}" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Akhir Berlaku</label>
    <input type="date" name="effective_end" class="form-control"
           value="{{ $effEnd }}">
    <small class="text-muted">Boleh kosong jika berlaku sampai dibatalkan.</small>
  </div>
</div>

<div class="row g-3 mt-2">
  <div class="col-md-4">
    <div class="form-check mt-2">
      <input class="form-check-input" type="checkbox" name="is_prorated" value="1"
             {{ $isProrated ? 'checked' : '' }}>
      <label class="form-check-label">Prorata</label>
    </div>
  </div>

  <div class="col-md-4">
    <div class="form-check mt-2">
      <input class="form-check-input" type="checkbox" name="allow_carry_over" value="1"
             {{ $allowCarryOver ? 'checked' : '' }}>
      <label class="form-check-label">Carry Over</label>
    </div>
  </div>

  <div class="col-md-4">
    {{-- (dummy col untuk menjaga grid rata) --}}
  </div>
</div> {{-- <<< TUTUP row checkbox (INI YANG HILANG) --}}

{{-- Cakupan --}}
<div class="row g-3 mt-2">
  <div class="col-md-4">
    <label class="form-label">Cakupan</label>
    <select name="applies_to" id="applies_to" class="form-control">
      <option value="all"        @selected($appliesTo==='all')>Semua Karyawan</option>
      <option value="department" @selected($appliesTo==='department')>Per Departemen</option>
      <option value="position"   @selected($appliesTo==='position')>Per Posisi</option>
    </select>
  </div>

  <div class="col-md-8">
    <label class="form-label">Pilih Nilai Cakupan</label>

    {{-- Department select --}}
    <select id="applies_department" class="form-control applies-select" style="width:100%; display:none;">
      <option value="">Pilih Departemen…</option>
      @foreach(($departments ?? collect()) as $d)
        <option value="{{ $d->id }}" @selected($appliesTo==='department' && (string)$appliesValue===(string)$d->id)>
          {{ $d->name }}
        </option>
      @endforeach
    </select>

    {{-- Position select --}}
    <select id="applies_position" class="form-control applies-select" style="width:100%; display:none;">
      <option value="">Pilih Posisi…</option>
      @foreach(($positions ?? collect()) as $p)
        <option value="{{ $p->id }}" @selected($appliesTo==='position' && (string)$appliesValue===(string)$p->id)>
          {{ $p->name }}
        </option>
      @endforeach
    </select>

    {{-- Hidden field yang dikirim ke server --}}
    <input type="hidden" name="applies_value" id="applies_value" value="{{ $appliesValue }}">
  </div>
</div>

{{-- Tombol --}}
<div class="mt-3 d-flex {{ class_exists(\Illuminate\Support\Arr::class) ? 'gap-2' : '' }}">
  <button class="btn btn-primary btn-pill"><i class="fas fa-save"></i> Simpan</button>
  <a href="{{ route('admin.leave-policies.index') }}" class="btn btn-light btn-pill ml-2">Batal</a>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>.select2-container .select2-selection--single{height:38px}</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  function syncAppliesVisibility() {
    const scope = document.getElementById('applies_to').value;
    const depSel = $('#applies_department');
    const posSel = $('#applies_position');
    const hidden = document.getElementById('applies_value');

    $('.applies-select').hide();

    if (scope === 'department') {
      depSel.show();
      hidden.value = depSel.val() || '';
    } else if (scope === 'position') {
      posSel.show();
      hidden.value = posSel.val() || '';
    } else {
      hidden.value = '';
    }
  }

  $(function() {
    $('#applies_department').select2({ placeholder:'Pilih Departemen…', width:'resolve' });
    $('#applies_position').select2({ placeholder:'Pilih Posisi…', width:'resolve' });

    syncAppliesVisibility();

    $('#applies_to').on('change', function() {
      $('#applies_department').val('').trigger('change');
      $('#applies_position').val('').trigger('change');
      syncAppliesVisibility();
    });

    $('#applies_department').on('change', function(){ $('#applies_value').val($(this).val() || ''); });
    $('#applies_position').on('change', function(){ $('#applies_value').val($(this).val() || ''); });
  });
</script>
@endpush
