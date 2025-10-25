@extends('layouts.master')
@section('title','Pay Components')

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
  /* teks badge Kind selalu kontras */
  .badge.kind-badge{ color:#fff!important; }
</style>
@endpush

@section('content')
{{-- HERO --}}
<div class="card card-hero shadow-soft mb-3">
  <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
    <div class="mb-2">
      <h4 class="mb-1">Pay Components</h4>
      <small class="text-muted">Kelola komponen gaji & navigasi ke daftar rate per komponen.</small>
    </div>
    <div class="d-flex">
      <a href="{{ route('admin.pay-components.create') }}" class="btn btn-primary rounded-pill">
        <i class="fas fa-plus mr-1"></i> New Component
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

{{-- FILTER CHIPS + SEARCH --}}
@php
  $kind   = request('kind');
  $active = request('active');
  $kinds  = ['earning','allowance','deduction','reimbursement'];
@endphp
<div class="d-flex flex-wrap align-items-center mb-3" style="gap:.5rem">
  {{-- chips kind --}}
  <span class="text-muted small mr-1">Kind:</span>
  <a href="{{ route('admin.pay-components.index', array_merge(request()->except('page','kind'), ['kind'=>null])) }}"
     class="chip {{ $kind===null ? 'is-active' : '' }}">All</a>
  @foreach($kinds as $k)
    <a href="{{ request()->fullUrlWithQuery(['kind'=>$k,'page'=>null]) }}"
       class="chip {{ $kind===$k ? 'is-active' : '' }}">{{ ucfirst($k) }}</a>
  @endforeach

  {{-- chips status --}}
  <span class="text-muted small ml-3 mr-1">Status:</span>
  <a href="{{ route('admin.pay-components.index', array_merge(request()->except('page','active'), ['active'=>null])) }}"
     class="chip {{ $active===null ? 'is-active' : '' }}">All</a>
  <a href="{{ request()->fullUrlWithQuery(['active'=>'1','page'=>null]) }}"
     class="chip {{ $active==='1' ? 'is-active' : '' }}">Active</a>
  <a href="{{ request()->fullUrlWithQuery(['active'=>'0','page'=>null]) }}"
     class="chip {{ $active==='0' ? 'is-active' : '' }}">Archived</a>

  {{-- search --}}
  <form method="get" class="ml-auto d-flex align-items-center" style="gap:.5rem">
    @if($kind!==null)<input type="hidden" name="kind" value="{{ $kind }}">@endif
    @if($active!==null)<input type="hidden" name="active" value="{{ $active }}">@endif
    <div class="input-icon">
      <i class="fas fa-search"></i>
      <input type="search" name="q" value="{{ request('q') }}" class="form-control rounded-pill"
             placeholder="Search code / name …">
    </div>
    <button class="btn btn-soft-primary rounded-pill">
      <i class="fas fa-filter mr-1"></i> Apply
    </button>
    <a href="{{ route('admin.pay-components.index') }}" class="btn btn-outline-secondary rounded-pill">Reset</a>
  </form>
</div>

{{-- QUICK STATS --}}
<div class="mb-2">
  <span class="badge badge-pill badge-light text-muted border">Total: {{ number_format($items->total()) }}</span>
  <span class="badge badge-pill badge-light text-muted border ml-1">Page: {{ $items->currentPage() }} / {{ $items->lastPage() }}</span>
</div>

{{-- TABLE --}}
<div class="card shadow-soft">
  <div class="table-responsive" style="max-height:65vh;">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th style="width:10%">Code</th>
          <th style="width:26%">Name</th>
          <th style="width:14%">Kind</th>
          <th style="width:12%">Calc</th>
          <th class="text-right" style="width:12%">Default</th>
          <th style="width:12%">Status</th>
          <th class="text-right" style="width:14%">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $it)
          <tr>
            <td class="font-weight-bold">{{ $it->code }}</td>
            <td>
              {{ $it->name }}
              @if(!empty($it->notes))
                <i class="far fa-comment-dots text-muted ml-1" data-toggle="tooltip" title="{{ \Illuminate\Support\Str::limit($it->notes, 160) }}"></i>
              @endif
            </td>
            <td>
              @php
                $kindColor = [
                  'earning' => 'primary',
                  'allowance' => 'success',
                  'deduction' => 'danger',
                  'reimbursement' => 'warning'
                ][$it->kind] ?? 'secondary';
              @endphp
              <span class="badge badge-pill kind-badge badge-{{ $kindColor }}">{{ ucfirst($it->kind) }}</span>
            </td>
            <td>
              <span class="badge badge-pill badge-light text-dark">{{ ucfirst($it->calc_type) }}</span>
            </td>
            <td class="text-right">
              {{ number_format((float)($it->default_amount ?? 0), 0, ',', '.') }}
            </td>
            <td>
              <span class="badge badge-pill {{ $it->active ? 'badge-success' : 'badge-secondary' }}">
                {{ $it->active ? 'Active' : 'Archived' }}
              </span>
            </td>
            <td class="text-right">
              <div class="dropdown">
                <button class="btn btn-sm btn-icon btn-light" type="button" data-toggle="dropdown">
                  <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                  <a class="dropdown-item" href="{{ route('admin.pay-components.rates.index', $it) }}">
                    <i class="fas fa-receipt fa-fw mr-2"></i>Rates
                  </a>
                  <a class="dropdown-item" href="{{ route('admin.pay-components.edit', $it) }}">
                    <i class="fas fa-pen fa-fw mr-2"></i>Edit
                  </a>
                  <div class="dropdown-divider"></div>
                  @if($it->active)
                    <a class="dropdown-item text-danger" href="#"
                       onclick="confirmArchive('{{ route('admin.pay-components.archive',$it) }}','{{ $it->code }}')">
                      <i class="fas fa-archive fa-fw mr-2"></i>Archive
                    </a>
                  @else
                    <a class="dropdown-item text-success" href="#" onclick="event.preventDefault(); this.closest('form').submit();">
                      <i class="fas fa-rotate-left fa-fw mr-2"></i>Activate
                      <form method="post" action="{{ route('admin.pay-components.activate', $it) }}" class="d-none">
                        @csrf
                      </form>
                    </a>
                  @endif
                </div>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center py-5">
              <div class="mb-2"><i class="fas fa-inbox text-muted"></i></div>
              <div class="text-muted">Belum ada komponen.
                <a href="{{ route('admin.pay-components.create') }}" class="ml-1">Buat sekarang</a>
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
      {{ $items->withQueryString()->onEachSide(1)->links('pagination::bootstrap-4') }}
    </div>
  @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmArchive(actionUrl, code) {
  Swal.fire({
    title: 'Archive component?',
    html: 'Komponen <b>'+code+'</b> akan diarsip.<br><small class="text-muted">Data tidak dihapus permanen.</small>',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, archive',
    cancelButtonText: 'Batal',
    reverseButtons: true,
    customClass: { confirmButton: 'rounded-pill', cancelButton: 'rounded-pill' }
  }).then((result) => {
    if (!result.isConfirmed) return;
    const form = document.createElement('form');
    form.method = 'POST'; form.action = actionUrl;
    form.innerHTML = `@csrf`;
    document.body.appendChild(form); form.submit();
  });
}

// tooltips (Bootstrap 4)
$(function(){ $('[data-toggle="tooltip"]').tooltip(); });
</script>
@endpush
