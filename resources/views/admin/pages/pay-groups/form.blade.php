@extends('layouts.master')
@section('title', $model->exists ? 'Edit Pay Group' : 'New Pay Group')

@section('content')
@if($errors->any())
  <div class="alert alert-danger">
    <div class="fw-bold mb-1">Please fix the errors below.</div>
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
@endif

<form method="post"
      action="{{ $model->exists ? route('admin.pay-groups.update',$model) : route('admin.pay-groups.store') }}"
      class="card">
  @csrf @if($model->exists) @method('PUT') @endif

  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>{{ $model->exists ? 'Edit' : 'Create' }} Pay Group</strong>
    @if($model->exists)
      <a href="{{ route('admin.pay-groups.components.index', ['pay_group'=>$model->id]) }}" class="btn btn-sm btn-outline-primary">
        <i class="fas fa-layer-group me-1"></i> Manage Components
      </a>
    @endif
  </div>

  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Code</label>
        <input name="code" value="{{ old('code',$model->code) }}"
               class="form-control @error('code') is-invalid @enderror"
               {{ $model->exists ? 'readonly' : '' }} required maxlength="32" autocomplete="off" id="pgCode">
        <div class="form-text">Unique identifier. {{ $model->exists ? 'Immutable.' : 'Auto-uppercase.' }}</div>
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-7">
        <label class="form-label">Name</label>
        <input name="name" value="{{ old('name',$model->name) }}"
               class="form-control @error('name') is-invalid @enderror" required maxlength="120">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-2">
        <label class="form-label d-block">Status</label>
        <div class="form-check form-switch mt-2">
          <input class="form-check-input" type="checkbox" id="activeSwitch" name="active" value="1" @checked(old('active', $model->active ?? true))>
          <label class="form-check-label" for="activeSwitch">Active</label>
        </div>
      </div>

      <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" maxlength="500">{{ old('notes',$model->notes) }}</textarea>
        <div class="form-text">Optional. Max 500 characters.</div>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      @isset($schedules)
        <div class="col-md-4">
          <label class="form-label">Schedule (optional)</label>
          <select name="schedule_id" class="form-select @error('schedule_id') is-invalid @enderror">
            <option value="">— None —</option>
            @foreach($schedules as $sch)
              <option value="{{ $sch->id }}" @selected(old('schedule_id', $model->schedule_id ?? null) == $sch->id)>
                {{ $sch->name }} ({{ $sch->daily_hours }}h/day)
              </option>
            @endforeach
          </select>
          <div class="form-text">Jika kosong, sistem pakai default {{ config('payroll.default_daily_hours',8) }} jam/hari.</div>
          @error('schedule_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      @endisset

    </div>
  </div>

  <div class="card-footer d-flex gap-2 justify-content-between">
    <a href="{{ route('admin.pay-groups.index') }}" class="btn btn-light">Back</a>
    <div class="d-flex gap-2">
      @if($model->exists)
        <a href="{{ route('admin.pay-groups.components.index', ['pay_group'=>$model->id]) }}" class="btn btn-outline-primary">
          <i class="fas fa-layer-group me-1"></i> Components
        </a>
      @endif
      <button class="btn btn-primary">Save</button>
    </div>
  </div>
</form>
@endsection

@push('scripts')
<script>
  (function(){
    // Auto uppercase code on create mode
    var code = document.getElementById('pgCode');
    if (code && !code.readOnly) {
      code.addEventListener('input', function(){ this.value = this.value.toUpperCase().replace(/\s+/g,'_'); });
    }
  })();
</script>
@endpush