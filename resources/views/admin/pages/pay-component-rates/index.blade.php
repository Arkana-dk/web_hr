@extends('layouts.master')
@section('title', 'Rates · '.$payComponent->code)

@php
  $today = \Illuminate\Support\Carbon::today();
@endphp

@section('content')

{{-- Hero Card --}}
<div class="card shadow-sm mb-4 border-0 rounded-3 bg-light">
  <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
    <div>
      <h4 class="fw-bold mb-1">{{ $payComponent->name }}</h4>
      <div class="text-muted">Kode: {{ $payComponent->code }}</div>
    </div>
    <div class="d-flex gap-3 mt-3 mt-md-0">
      <div class="card shadow-sm border-0 text-center px-3 py-2">
        <div class="fs-5 fw-bold">{{ $items->total() }}</div>
        <small class="text-muted">Total Rates</small>
      </div>
      <div class="card shadow-sm border-0 text-center px-3 py-2">
        <div class="fs-5 fw-bold text-success">{{ $activeCount }}</div>
        <small class="text-muted">Active</small>
      </div>
    </div>
            @php
          $activeCount = $items->getCollection()->filter(function($r) use ($today){
            $start = $r->effective_start ? \Illuminate\Support\Carbon::parse($r->effective_start) : null;
            $end   = $r->effective_end   ? \Illuminate\Support\Carbon::parse($r->effective_end)   : null;
            $startOk = $start && $start->lte($today);
            $endOk   = !$end || $end->gte($today);
            return $startOk && $endOk;
          })->count();
        @endphp
    <div class="mt-3 mt-md-0 d-flex gap-2">
      <a href="{{ route('admin.pay-components.index') }}" class="btn btn-light btn-pill">Back</a>
      <a href="{{ route('admin.pay-components.rates.create', $payComponent) }}" class="btn btn-primary btn-pill">+ Add Rate</a>
    </div>
  </div>
</div>


{{-- Alerts --}}
@foreach (['success'=>'success','error'=>'danger','info'=>'info'] as $key=>$cls)
  @if (session($key))
    <div class="alert alert-{{ $cls }}">{{ session($key) }}</div>
  @endif
@endforeach

{{-- Filters --}}
<div class="card mb-3">
  <div class="card-body">
    <div class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label">Search</label>
        <input id="filter-search" type="search" class="form-control" placeholder="Search unit, formula, pay group...">
      </div>
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select id="filter-status" class="form-select">
          <option value="">All</option>
          <option value="active">Active</option>
          <option value="expired">Expired</option>
          <option value="future">Upcoming</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Pay Group</label>
        <select id="filter-group" class="form-select">
          <option value="">All</option>
          @foreach(($payGroups ?? []) as $g)
            <option value="{{ $g->id }}">{{ $g->name }}</option>
          @endforeach
          <option value="__none">(No Group)</option>
        </select>
      </div>
      <div class="col-md-2 text-md-end">
        <span class="badge bg-secondary me-1">Total: {{ $items->total() }}</span>
        <span class="badge bg-success">Active: {{ $activeCount }}</span>
      </div>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="table-responsive bg-white rounded shadow-sm">
  <table class="table table-hover align-middle mb-0" id="rates-table">
    <thead class="table-light">
      <tr>
        <th style="width:14rem">Unit</th>
        <th class="text-end" style="width:12rem">Rate</th>
        <th>Pay Group</th>
        <th style="width:20rem">Effective</th>
        <th class="text-end" style="width:10rem">Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($items as $r)
        @php
          $start = $r->effective_start ? \Illuminate\Support\Carbon::parse($r->effective_start) : null;
          $end   = $r->effective_end   ? \Illuminate\Support\Carbon::parse($r->effective_end)   : null;
          $startOk = $start && $start->lte($today);
          $endOk   = !$end || $end->gte($today);
          $status  = $startOk && $endOk ? 'active' : ($start && $start->gt($today) ? 'future' : 'expired');
        @endphp
        <tr
          data-status="{{ $status }}"
          data-group="{{ $r->pay_group_id ?? '__none' }}"
          data-text="{{ strtolower(trim(($r->unit ?? '').' '.($r->formula ?? '').' '.(optional($r->payGroup)->name ?? 'none'))) }}">
          <td>
            <span class="fw-semibold">{{ $r->unit ?: '—' }}</span>
            @if($r->formula)
              <span class="ms-2 badge rounded-pill bg-info-subtle text-info" data-bs-toggle="tooltip" title="Formula">fx</span>
            @endif
          </td>
          <td class="text-end">{{ number_format($r->rate, 2, ',', '.') }}</td>
          <td>{{ optional($r->payGroup)->name ?? '—' }}</td>
          <td>
            <span>
              {{ $start ? $start->format('d M Y') : '—' }} — {{ $end ? $end->format('d M Y') : '∞' }}
            </span>
            @if($status==='active')
              <span class="ms-2 badge bg-success text-white">Active</span>
            @elseif($status==='future')
              <span class="ms-2 badge bg-warning text-white">Upcoming</span>
            @else
              <span class="ms-2 badge bg-danger text-white">Expired</span>
            @endif

          </td>
          <td class="text-end">
            <div class="btn-group">
              <a href="{{ route('admin.rates.edit',$r) }}" class="btn btn-sm btn-outline-secondary btn-pill">Edit</a>
              <button class="btn btn-sm btn-outline-danger btn-pill"
                      data-bs-toggle="modal"
                      data-bs-target="#confirmDeleteModal"
                      data-action="{{ route('admin.rates.destroy',$r) }}">
                Delete
              </button>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="text-muted text-center py-4">Belum ada rate.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="mt-3">{{ $items->withQueryString()->links() }}</div>

{{-- Delete confirm modal --}}
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Delete Rate</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">Yakin mau menghapus rate ini?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light btn-pill" data-bs-dismiss="modal">Batal</button>
        <form id="deleteForm" method="post">@csrf @method('delete')
          <button class="btn btn-danger btn-pill">Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .btn-pill { border-radius: 50rem !important; }
</style>
@endpush

@push('scripts')
<script>
  (function(){
    const table = document.getElementById('rates-table');
    const q = document.getElementById('filter-search');
    const statusSel = document.getElementById('filter-status');
    const groupSel = document.getElementById('filter-group');

    function applyFilter(){
      const text = (q?.value || '').toLowerCase();
      const st   = statusSel?.value || '';
      const gid  = groupSel?.value || '';
      table.querySelectorAll('tbody tr').forEach(tr => {
        const rowText = (tr.getAttribute('data-text') || '');
        const matchText = rowText.indexOf(text) !== -1;
        const matchStatus = !st || tr.getAttribute('data-status') === st;
        const matchGroup  = !gid || tr.getAttribute('data-group') === gid;
        tr.style.display = (matchText && matchStatus && matchGroup) ? '' : 'none';
      });
    }

    ['input','change'].forEach(evt => {
      q?.addEventListener(evt, applyFilter);
      statusSel?.addEventListener(evt, applyFilter);
      groupSel?.addEventListener(evt, applyFilter);
    });

    const modal = document.getElementById('confirmDeleteModal');
    modal.addEventListener('show.bs.modal', function (e) {
      const btn = e.relatedTarget;
      const action = btn.getAttribute('data-action');
      document.getElementById('deleteForm').setAttribute('action', action);
    });

    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });
  })();
</script>
@endpush
