@extends('layouts.master')
@section('title','Pay Groups')

@push('styles')
<style>
  :root{
    --soft-bg: rgba(78,115,223,.10);
    --soft-bd: rgba(78,115,223,.25);
  }
  .rounded-pill{ border-radius:999px!important; }
  .card-hero{
    background: linear-gradient(135deg,#f6f9ff,#fff);
    border: 1px solid #eef2ff;
    border-radius: 1rem;
  }
  .shadow-soft{ box-shadow: 0 16px 35px -20px rgba(0,0,0,.35); }
  .btn-soft-primary{ background: var(--soft-bg); border-color: var(--soft-bd); color:#4e73df; }
  .btn-soft-primary:hover{ background: rgba(78,115,223,.14); }
  .chip{ display:inline-flex; align-items:center; padding:.35rem .75rem; border-radius:999px; border:1px solid #e6e9f2; font-weight:600; font-size:.85rem; color:#6c757d; }
  .chip.is-active{ background:#e8f0ff; border-color:#cfe0ff; color:#356dff; }
  .table thead th{ background:#fff; position:sticky; top:0; z-index:1; }
  .badge-pill{ border-radius:999px; padding:.4rem .65rem; font-weight:600; font-size:.75rem; }
  .input-icon{ position:relative; }
  .input-icon > i{ position:absolute; left:.75rem; top:50%; transform:translateY(-50%); color:#9aa0a6; }
  .input-icon > input{ padding-left:2.2rem; }
  /* amankan pagination */
  .pagination svg{ width:1rem; height:1rem; vertical-align:-.125em; }
  .pagination .hidden{ display:none!important; }
</style>
@endpush

@section('content')
{{-- HERO / HEADER --}}
<div class="card card-hero shadow-soft mb-3">
  <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
    <div class="mb-2">
      <h4 class="mb-1">Pay Groups</h4>
      <small class="text-muted">Kelola grup payroll & komponen yang melekat di dalamnya.</small>
    </div>
    <div class="d-flex">
      <a href="{{ route('admin.pay-groups.create') }}" class="btn btn-primary rounded-pill">
        <i class="fas fa-plus mr-1"></i> New Group
      </a>
    </div>
  </div>
</div>

{{-- FLASH --}}
@foreach (['success'=>'success','error'=>'danger','info'=>'info'] as $key=>$cls)
  @if (session($key))
    <div class="alert alert-{{ $cls }} rounded-pill px-3 py-2 d-inline-flex align-items-center">
      <i class="fas fa-info-circle mr-2"></i> {{ session($key) }}
    </div>
  @endif
@endforeach

{{-- FILTERS --}}
@php $active = request('active'); @endphp
<div class="d-flex flex-wrap align-items-center mb-3" style="gap:.5rem">
  {{-- chips status --}}
  <a href="{{ route('admin.pay-groups.index', array_merge(request()->except('page'), ['active'=>null])) }}"
     class="chip {{ $active===null ? 'is-active' : '' }}">All</a>
  <a href="{{ request()->fullUrlWithQuery(['active'=>'1','page'=>null]) }}"
     class="chip {{ $active==='1' ? 'is-active' : '' }}">Active</a>
  <a href="{{ request()->fullUrlWithQuery(['active'=>'0','page'=>null]) }}"
     class="chip {{ $active==='0' ? 'is-active' : '' }}">Archived</a>

  {{-- search --}}
  <form method="get" class="ml-auto d-flex align-items-center" style="gap:.5rem">
    @if($active!==null)
      <input type="hidden" name="active" value="{{ $active }}">
    @endif
    <div class="input-icon">
      <i class="fas fa-search"></i>
      <input name="q" value="{{ request('q') }}" class="form-control rounded-pill"
             placeholder="Search code / name / notes…">
    </div>
    <button class="btn btn-soft-primary rounded-pill">
      <i class="fas fa-filter mr-1"></i> Apply
    </button>
    <a href="{{ route('admin.pay-groups.index') }}" class="btn btn-outline-secondary rounded-pill">Reset</a>
  </form>
</div>

{{-- QUICK STATS --}}
<div class="mb-2">
  <span class="badge badge-pill badge-light text-muted border">Total: {{ number_format($items->total()) }}</span>
  <span class="badge badge-pill badge-light text-muted border ml-1">Page: {{ $items->currentPage() }} / {{ $items->lastPage() }}</span>
</div>

{{-- TABLE --}}
<div class="card shadow-soft">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th style="width:14rem">Code</th>
          <th>Name & Notes</th>
          <th style="width:10rem">Status</th>
          <th class="text-right" style="width:20rem">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $g)
          <tr>
            <td class="font-weight-bold">{{ $g->code }}</td>
            <td>
              <div class="font-weight-normal">{{ $g->name }}</div>
              @if($g->notes)
                <div class="small text-muted">{{ \Illuminate\Support\Str::limit($g->notes, 120) }}</div>
              @endif
            </td>
            <td>
              <span class="badge badge-pill {{ $g->active ? 'badge-success' : 'badge-secondary' }}">
                {{ $g->active ? 'Active' : 'Archived' }}
              </span>
            </td>
            <td class="text-right">
              <div class="btn-group" role="group">
                <a href="{{ route('admin.pay-groups.components.index', ['pay_group'=>$g->id]) }}"
                   class="btn btn-sm btn-outline-primary rounded-pill mr-1">
                  <i class="fas fa-layer-group mr-1"></i> Components
                </a>
                <a href="{{ route('admin.pay-groups.edit', $g) }}"
                   class="btn btn-sm btn-outline-secondary rounded-pill mr-1">
                  <i class="fas fa-pen mr-1"></i> Edit
                </a>
                @if($g->active)
                  <button type="button"
                          class="btn btn-sm btn-outline-danger rounded-pill"
                          data-action="{{ route('admin.pay-groups.destroy',$g) }}"
                          data-name="{{ $g->code }}"
                          onclick="confirmArchive(this)">
                    <i class="fas fa-archive mr-1"></i> Archive
                  </button>
                @else
                  <form method="post" action="{{ route('admin.pay-groups.update', $g) }}" class="d-inline">
                    @csrf @method('PUT')
                    <input type="hidden" name="active" value="1">
                    <button class="btn btn-sm btn-outline-success rounded-pill">
                      <i class="fas fa-rotate-left mr-1"></i> Activate
                    </button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="text-center py-5">
              <div class="mb-2"><i class="fas fa-inbox text-muted"></i></div>
              <div class="text-muted">Belum ada pay group yang cocok.
                <a href="{{ route('admin.pay-groups.create') }}" class="ml-1">Buat sekarang</a>
              </div>
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
      {{-- pakai view bootstrap-4 supaya ikon pagination rapi --}}
      {{ $items->withQueryString()->onEachSide(1)->links('pagination::bootstrap-4') }}
    </div>
  @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmArchive(btn){
  const actionUrl = btn.getAttribute('data-action');
  const code = btn.getAttribute('data-name');
  Swal.fire({
    title: 'Archive group?',
    html: 'Grup <b>'+ (code || '(unknown)') +'</b> akan diarsip.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, archive',
    cancelButtonText: 'Batal',
    reverseButtons: true,
    customClass: { confirmButton: 'rounded-pill', cancelButton: 'rounded-pill' }
  }).then(r=>{
    if(!r.isConfirmed) return;
    const f=document.createElement('form');
    f.method='POST'; f.action=actionUrl;
    f.innerHTML=`@csrf @method('DELETE')`;
    document.body.appendChild(f); f.submit();
  });
}
</script>
@endpush
