@extends('layouts.master')
@section('title', 'Components · '.$payGroup->name)

@push('styles')
<style>
  .card-rounded{ border-radius: 1rem; }
  .shadow-soft{ box-shadow: 0 14px 30px -14px rgba(0,0,0,.25); }
  .rounded-pill{ border-radius: 999px !important; }
  .btn-soft-primary{ background: rgba(78,115,223,.10); border-color: rgba(78,115,223,.25); color:#4e73df; }
  .btn-soft-primary:hover{ background: rgba(78,115,223,.14); }
  .badge-pill{ border-radius:999px; padding:.4rem .65rem; font-weight:600; font-size:.75rem; }
  .table thead.thead-light th{ background:#fff; position: sticky; top:0; z-index:1; }
  /* Safety net jika masih ada SVG pagination dari template lain */
  .pagination svg { width: 1rem; height: 1rem; vertical-align: -0.125em; }
  .pagination .hidden { display: none !important; }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
  <div class="mb-2">
    <h4 class="mb-0">{{ $payGroup->name }} — Components</h4>
    <small class="text-muted">Kelola komponen yang berlaku untuk grup ini.</small>
  </div>

  <div class="d-flex">
    <a href="{{ route('admin.pay-groups.index') }}"
       class="btn btn-outline-secondary rounded-pill mr-2">
      <i class="fas fa-arrow-left mr-1"></i> Kembali
    </a>

    <a href="{{ route('admin.pay-groups.components.create', $payGroup) }}"
       class="btn btn-primary rounded-pill">
      <i class="fas fa-plus mr-1"></i> Link Component
    </a>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success rounded-pill px-3 py-2 d-inline-flex align-items-center">
    <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
  </div>
@endif

<div class="card shadow-soft card-rounded">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="thead-light">
        <tr>
          <th style="width:10%">Seq</th>
          <th style="width:40%">Component</th>
          <th style="width:14%">Mandatory</th>
          <th style="width:14%">Status</th>
          <th class="text-right" style="width:22%">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $i)
        <tr>
          <td>{{ $i->sequence }}</td>
          <td>
            <div class="font-weight-bold">{{ $i->component->name ?? '-' }}</div>
            <div class="text-muted small">{{ $i->component->code ?? '' }}</div>
          </td>
          <td>
            <span class="badge badge-pill {{ $i->mandatory ? 'badge-info' : 'badge-secondary' }}">
              {{ $i->mandatory ? 'Yes' : 'No' }}
            </span>
          </td>
          <td>
            <span class="badge badge-pill {{ $i->active ? 'badge-success' : 'badge-secondary' }}">
              {{ $i->active ? 'Active' : 'Inactive' }}
            </span>
          </td>
          <td class="text-right">
            <div class="btn-group" role="group">
              <a href="{{ route('admin.components.edit', $i) }}"
                 class="btn btn-sm btn-outline-secondary rounded-pill mr-1">
                <i class="fas fa-pen mr-1"></i> Edit
              </a>
              <button type="button"
                      class="btn btn-sm btn-outline-danger rounded-pill"
                      onclick="confirmRemove('{{ route('admin.components.destroy', $i) }}', '{{ $i->component->code ?? '' }}')">
                <i class="fas fa-times mr-1"></i> Remove
              </button>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="5" class="text-center text-muted py-5">
            Belum ada komponen di grup ini. <br>
            <a href="{{ route('admin.pay-groups.components.create', $payGroup) }}"
               class="btn btn-soft-primary rounded-pill mt-3">
              <i class="fas fa-plus mr-1"></i> Link Component
            </a>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($items->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap">
      <small class="text-muted mb-2 mb-sm-0">
        Menampilkan {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} data
      </small>
      {{-- pakai view bootstrap-4 biar clean, tanpa SVG --}}
      {{ $items->onEachSide(1)->links('pagination::bootstrap-4') }}
    </div>
  @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmRemove(actionUrl, code) {
  Swal.fire({
    title: 'Remove component from group?',
    html: 'Komponen <b>'+ (code || '(unknown)') +'</b> akan dihapus dari grup ini.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, hapus',
    cancelButtonText: 'Batal',
    reverseButtons: true,
    customClass: { confirmButton: 'rounded-pill', cancelButton: 'rounded-pill' }
  }).then((res) => {
    if (res.isConfirmed) {
      const f = document.createElement('form');
      f.method = 'POST'; f.action = actionUrl;
      f.innerHTML = `@csrf @method('DELETE')`;
      document.body.appendChild(f);
      f.submit();
    }
  });
}
</script>
@endpush
