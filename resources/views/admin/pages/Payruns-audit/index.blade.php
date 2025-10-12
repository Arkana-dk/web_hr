@extends('layouts.master')

@section('title', 'Payrun Audit')

@push('styles')
<style>
  .card-hero{
    background: linear-gradient(135deg,#f6f9ff,#fff);
    border: 1px solid #eef2ff; border-radius: 1rem;
  }
  .table thead th{ position: sticky; top:0; background:#fff; z-index:1; }
  .badge-soft{ border:1px solid rgba(0,0,0,.08); background: rgba(0,0,0,.03); font-weight:600; }
  .badge-action{ text-transform: capitalize; }
  .empty{ padding:3rem 1rem; text-align:center; color:#64748b; }
</style>
@endpush

@section('content')
<div class="container-fluid">

  <div class="card card-hero shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center">
        <div class="mb-3 mb-lg-0">
          <h4 class="mb-1">📜 Payrun Audit Trail</h4>
          <div class="text-muted">Telusuri jejak aktivitas payroll: siapa, apa, kapan, dan pada payrun mana.</div>
        </div>
        <div>
          <a href="{{ route('admin.payruns-audit.index') }}" class="btn btn-light border">
            <i class="fas fa-undo"></i> Reset
          </a>
        </div>
      </div>

      {{-- FILTERS --}}
      <form method="GET" action="{{ route('admin.payruns-audit.index') }}" class="mt-3">
        <div class="form-row">
          <div class="col-md-3 mb-2">
            <label class="mb-1">Rentang Tanggal</label>
            <div class="d-flex">
              <input type="date" name="date_from" class="form-control mr-1"
                     value="{{ $dateFrom ?? '' }}" placeholder="Dari">
              <input type="date" name="date_to" class="form-control ml-1"
                     value="{{ $dateTo ?? '' }}" placeholder="Sampai">
            </div>
          </div>

          <div class="col-md-3 mb-2">
            <label class="mb-1">Aksi</label>
            <select name="action" class="form-control">
              <option value="">— Semua aksi —</option>
              @foreach(['FINALIZE','REOPEN'] as $opt)
                <option value="{{ $opt }}" {{ $action===$opt ? 'selected' : '' }}>
                  {{ ucfirst(strtolower($opt)) }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-3 mb-2">
            <label class="mb-1">Payrun</label>
            <select name="pay_run_id" class="form-control">
              <option value="">— Semua payrun —</option>
              @foreach($payruns as $p)
                <option value="{{ $p->id }}" {{ $payRunId==$p->id ? 'selected' : '' }}>Payrun #{{ $p->id }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-3 mb-2 align-self-end text-md-right">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-search"></i> Terapkan Filter
            </button>
          </div>
        </div>
      </form>

    </div>
  </div>

  {{-- TABLE --}}
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Waktu</th>
            <th>Aktor</th>
            <th>Aksi</th>
            <th>Payrun</th>
            <th>Keterangan</th>
          </tr>
        </thead>
        <tbody>
        @forelse($audits as $audit)
          @php
            $color = match($audit->action) {
              'FINALIZE' => 'success',
              'REOPEN'   => 'warning',
              default    => 'secondary'
            };
          @endphp
          <tr>
            <td>
              {{ optional($audit->created_at)->format('d M Y H:i') }}
              <div class="small text-muted">{{ optional($audit->created_at)->diffForHumans() }}</div>
            </td>
            <td>
              <span class="badge badge-soft">{{ $audit->actor->name ?? '—' }}</span>
            </td>
            <td>
              <span class="badge badge-{{ $color }} badge-action">
                {{ ucfirst(strtolower($audit->action)) }}
              </span>
            </td>
            <td>
              @if(optional($audit->payRun)->id)
                Payrun #{{ $audit->payRun->id }}
              @else
                —
              @endif
            </td>
            <td>{{ $audit->description }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-center text-muted py-4">
              Tidak ada data audit yang ditemukan.
            </td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>

    @if($audits instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="card-footer d-flex justify-content-between align-items-center">
      <div class="text-muted small">
        Menampilkan {{ $audits->count() }} dari total {{ $audits->total() }} catatan.
      </div>
      <div>{{ $audits->appends(request()->query())->links() }}</div>
    </div>
    @endif
  </div>
</div>
@endsection
