@extends('layouts.master')
@section('title','Generate Entitlements')
@include('components.leave.styles-soft')

@section('content')
<div class="container-fluid">

  {{-- HERO --}}
  <div class="card hero-card mb-3">
    <div class="hero-body d-flex justify-content-between align-items-center">
      <div>
        <div class="h3 fw-bold mb-1">Generate Entitlements</div>
        <div class="opacity-75">Buat hak cuti massal per tahun & jenis cuti</div>
      </div>
      <a href="{{ route('admin.leave-entitlements.index') }}" class="btn btn-soft-white btn-pill btn-elev">
        <i class="fas fa-list"></i> Daftar Entitlements
      </a>
    </div>
  </div>

  {{-- FORM --}}
  <div class="card soft">
    <div class="card-header">Parameter</div>
    <div class="card-body">
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif
      @if($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('admin.leave-entitlements.generate.store') }}">
        @csrf

        <div class="row g-3">
          <div class="col-md-2">
            <label class="form-label">Tahun</label>
            <input type="number" name="year" class="form-control" min="2000" max="2100"
                   value="{{ old('year', $defaultYear) }}" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Jenis Cuti</label>
            <select name="leave_type_ids[]" id="leave_type_ids" class="form-select" multiple required>
              @foreach($leaveTypes as $lt)
                <option value="{{ $lt->id }}" @selected(collect(old('leave_type_ids',[]))->contains($lt->id))>
                  {{ $lt->name }}
                </option>
              @endforeach
            </select>
            <div class="form-text">Bisa pilih lebih dari satu jenis cuti.</div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Mode</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="mode" id="mode_all" value="all"
                       {{ old('mode','all') === 'all' ? 'checked' : '' }}>
                <label class="form-check-label" for="mode_all">Semua Karyawan</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="mode" id="mode_selected" value="selected"
                       {{ old('mode') === 'selected' ? 'checked' : '' }}>
                <label class="form-check-label" for="mode_selected">Pilih Karyawan</label>
              </div>
            </div>
          </div>

          <div class="col-12" id="employee_picker_wrap" style="display: none;">
            <label class="form-label">Karyawan</label>
            <select name="employee_ids[]" id="employee_ids" class="form-select" multiple></select>
            <div class="form-text">Cari dengan nomor karyawan atau nama. Bisa pilih lebih dari satu.</div>
            @error('employee_ids') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>

          <div class="col-12">
            <div class="form-check mt-1">
              <input class="form-check-input" type="checkbox" name="overwrite" id="overwrite" value="1"
                     {{ old('overwrite') ? 'checked' : '' }}>
              <label class="form-check-label" for="overwrite">
                Overwrite jika sudah ada entitlement pada periode yang sama
              </label>
            </div>
          </div>
        </div>

        <div class="mt-3 d-flex gap-2">
          <button class="btn btn-primary btn-pill"><i class="fas fa-bolt"></i> Generate</button>
          <a href="{{ route('admin.leave-entitlements.index') }}" class="btn btn-light btn-pill">Batal</a>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection

@push('styles')
  {{-- TomSelect CSS (atau ganti ke Select2 jika kamu prefer) --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
@endpush

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
  <script>
  (function(){
    // multi select untuk jenis cuti (enhance UX)
    new TomSelect('#leave_type_ids', {
      plugins: ['remove_button'],
      create: false,
      persist: false,
      maxOptions: 500,
      placeholder: 'Pilih jenis cuti...',
      render: { option: function(data, escape){ return '<div>'+escape(data.text)+'</div>'; } }
    });

    // employee picker (ajax)
    const employeePickerWrap = document.getElementById('employee_picker_wrap');
    const modeAll     = document.getElementById('mode_all');
    const modeSelected= document.getElementById('mode_selected');

    function toggleEmployeePicker(){
      employeePickerWrap.style.display = modeSelected.checked ? 'block' : 'none';
    }
    modeAll.addEventListener('change', toggleEmployeePicker);
    modeSelected.addEventListener('change', toggleEmployeePicker);
    toggleEmployeePicker();

    const employeeSelect = new TomSelect('#employee_ids', {
      valueField: 'id',
      labelField: 'text',
      searchField: 'text',
      plugins: ['remove_button'],
      create: false,
      persist: false,
      maxOptions: 500,
      load: function(query, callback) {
        const url = '{{ route('admin.leave-entitlements.employee-search') }}' + '?q=' + encodeURIComponent(query || '');
        fetch(url, {headers: {'X-Requested-With':'XMLHttpRequest'}})
          .then(res => res.json())
          .then(json => callback(json))
          .catch(()=>callback());
      },
      placeholder: 'Ketik nama / nomor karyawan...',
      render: {
        item: function(item, escape) { return '<div>'+escape(item.text)+'</div>'; },
        option: function(item, escape) { return '<div>'+escape(item.text)+'</div>'; }
      }
    });

    // preload pilihan lama (jika ada old input)
    @if(is_array(old('employee_ids')))
      (function preload(){
        const vals = @json(old('employee_ids'));
        const labels = []; // biarkan kosong; TomSelect akan fetch saat search
        vals.forEach(v => employeeSelect.addOption({id:v, text:'ID:'+v}));
        employeeSelect.setValue(vals);
      })();
    @endif
  })();
  </script>
@endpush
