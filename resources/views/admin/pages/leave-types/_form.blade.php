@csrf
<div class="row g-3">
  <div class="col-md-4">
    <label class="form-label">Kode</label>
    <input type="text" name="code" class="form-control"
           value="{{ old('code', $type->code ?? '') }}" required>
  </div>
  <div class="col-md-8">
    <label class="form-label">Nama</label>
    <input type="text" name="name" class="form-control"
           value="{{ old('name', $type->name ?? '') }}" required>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-md-4">
    <div class="form-check mt-2">
      <input class="form-check-input" type="checkbox" name="is_paid" value="1"
             {{ old('is_paid', $type->is_paid ?? false) ? 'checked' : '' }}>
      <label class="form-check-label">Berbayar (Paid)</label>
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-check mt-2">
      <input class="form-check-input" type="checkbox" name="requires_attachment" value="1"
             {{ old('requires_attachment', $type->requires_attachment ?? false) ? 'checked' : '' }}>
      <label class="form-check-label">Butuh Lampiran</label>
    </div>
  </div>
</div>

<div class="mt-3 d-flex gap-2">
  <button class="btn btn-primary btn-pill"><i class="fas fa-save"></i> Simpan</button>
  <a href="{{ route('admin.leave-types.index') }}" class="btn btn-light btn-pill">Batal</a>
</div>
