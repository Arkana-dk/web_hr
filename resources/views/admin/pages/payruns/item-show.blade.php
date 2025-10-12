@extends('layouts.master')
@section('title','Item Details · Pay Run #'.$payrun->id)

@push('styles')
<style>
  :root{
    --hero-grad: linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%);
    --chip-bg: rgba(13,110,253,.08);
    --chip-bd: rgba(13,110,253,.2);
  }

  /* ===== HERO (match review) ===== */
  .hero-card{
    background: var(--hero-grad);
    color: #fff;
    border: 0;
    border-radius: 1.25rem;
    overflow: hidden;
  }
  .hero-card .hero-body{ padding: 1.25rem 1.25rem; }
  @media (min-width: 768px){ .hero-card .hero-body{ padding: 1.75rem 1.75rem; } }
  .hero-title{ font-weight: 700; letter-spacing: .2px; }
  .hero-meta{ opacity:.95 }
  .hero-badges{ display:flex; flex-wrap:wrap; gap:.5rem; }
  .tag-soft{
    background: rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.28);
    color:#fff;
    padding:.35rem .6rem;
    border-radius: 999px;
    font-size:.78rem;
    font-weight:600;
    white-space:nowrap;
  }

  /* ===== BUTTONS ===== */
  .btn-pill{ border-radius: 999px; }
  .btn-elev{ box-shadow: 0 8px 24px -10px rgba(13,110,253,.6); }
  .btn-soft-white{
    background: rgba(255,255,255,.15);
    border-color: rgba(255,255,255,.25);
    color: #fff;
  }
  .btn-soft-white:hover{ background: rgba(255,255,255,.25); color:#fff; }

  /* ===== SUMMARY ===== */
  .stat-card{ border: 1px solid rgba(0,0,0,.06); border-radius: 1rem; }
  .summary-grid{ display:grid; grid-template-columns:1fr; gap:.75rem }
  @media (min-width:576px){ .summary-grid{ grid-template-columns:repeat(3,minmax(0,1fr)); } }
  .summary-item{ background:#f8f9fa; border-radius:.9rem; padding:.8rem 1rem; }
  .summary-item .label{ font-size:.8rem; color:#6c757d; margin-bottom:.25rem }
  .summary-item .value{ font-weight:700; font-variant-numeric: tabular-nums; text-align:right }

  /* ===== TABLE ===== */
  .table-sticky thead th{ position:sticky; top:0; background:#fff; z-index:2; }
  .status-badge{ text-transform:uppercase; letter-spacing:.02em; }
  .table thead th{ border-top:0; }
  .table-hover tbody tr{ transition: background-color .12s ease; }
  .badge-side{ text-transform:uppercase; letter-spacing:.02em; color:#fff !important; }

</style>
@endpush

@section('content')
<div class="container py-4">

  @php
    $fmt = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
    $statusClass = [
      'draft'     => 'bg-secondary',
      'simulated' => 'bg-info',
      'finalized' => 'bg-primary',
      'locked'    => 'bg-dark',
    ][$payrun->status] ?? 'bg-secondary';

    // badge status untuk item (ok/warning/error) jika ada
    $itemBadge = match ($item->result_status ?? null) {
      'ok' => 'bg-success',
      'warning' => 'bg-warning text-dark',
      'error' => 'bg-danger',
      default => 'bg-secondary'
    };
  @endphp

  {{-- ========= HERO (senada dengan review) ========= --}}
  <div class="card hero-card shadow-sm mb-3">
    <div class="hero-body d-flex flex-column gap-3 gap-md-0 flex-md-row justify-content-between align-items-md-center">
      <div>
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="tag-soft">Pay Run #{{ $payrun->id }}</span>
          <span class="tag-soft"><i class="bi bi-person me-1"></i>{{ optional($item->employee)->name ?? '—' }}</span>
          <span class="tag-soft"><i class="bi bi-hash me-1"></i>Item #{{ $item->id }}</span>
          <span class="badge status-badge {{ $statusClass }}">{{ $payrun->status }}</span>
          @if(!empty($item->result_status))
            <span class="badge status-badge {{ $itemBadge }}">{{ $item->result_status }}</span>
          @endif
        </div>
        <h2 class="hero-title mb-1">Detail Item Pay Run</h2>
        <div class="hero-meta small">
          Periode:
          <strong>{{ $payrun->start_date->format('d M Y') }}</strong>
          s/d
          <strong>{{ $payrun->end_date->format('d M Y') }}</strong>
          @if(!empty($payrun->finalized_at))
            <span class="ms-2">• Finalized: {{ optional($payrun->finalized_at)->format('d M Y H:i') }}</span>
          @endif
          @if(!empty($payrun->locked_at))
            <span class="ms-2">• Locked: {{ optional($payrun->locked_at)->format('d M Y H:i') }}</span>
          @endif
        </div>
        <div class="hero-badges mt-2">
          <span class="tag-soft"><i class="bi bi-cash-stack me-1"></i> Earn: {{ $fmt($sumEarn) }}</span>
          <span class="tag-soft"><i class="bi bi-wallet2 me-1"></i> Deduct: {{ $fmt($sumDed) }}</span>
          <span class="tag-soft"><i class="bi bi-cash-coin me-1"></i> Net: {{ $fmt($item->net_pay) }}</span>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.payruns.review', $payrun) }}" class="btn btn-soft-white btn-pill">
          <i class="bi bi-arrow-left-short me-1"></i> Kembali ke Review
        </a>
      </div>
    </div>
  </div>

  {{-- ===== SUMMARY STRIP (optional, biar konsisten visual) ===== --}}
  <div class="row g-3 mb-3 align-items-stretch">
    <div class="col-12 col-md-4">
      <div class="card stat-card shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Earnings (item)</div>
            <div class="fs-5 fw-semibold">{{ $fmt($sumEarn) }}</div>
          </div>
          <span class="badge bg-success badge-side">earning</span>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card stat-card shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Deductions (item)</div>
            <div class="fs-5 fw-semibold">{{ $fmt($sumDed) }}</div>
          </div>
          <span class="badge bg-danger badge-side">deduction</span>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card stat-card shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Net (item)</div>
            <div class="fs-5 fw-semibold">{{ $fmt($item->net_pay) }}</div>
          </div>
          <i class="bi bi-cash-coin fs-3 text-secondary"></i>
        </div>
      </div>
    </div>
  </div>

  {{-- ===== Helper untuk tampilan aman (hindari htmlspecialchars array) ===== --}}
  @php
    $toDisplay = function ($v, $fallback = '—') {
      if ($v === null || $v === '') return $fallback;
      if (is_numeric($v)) {
        return number_format($v + 0, is_float($v + 0) ? 2 : 0, ',', '.');
      }
      if (is_string($v)) return $v;
      if (is_array($v)) {
        $flat = [];
        array_walk_recursive($v, function ($val) use (&$flat) {
          $flat[] = is_scalar($val) ? (string)$val : json_encode($val, JSON_UNESCAPED_UNICODE);
        });
        return implode(', ', $flat);
      }
      return json_encode($v, JSON_UNESCAPED_UNICODE);
    };
  @endphp

  {{-- ===== TABLE: Komponen & Rate ===== --}}
  <div class="card shadow rounded-4">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 table-sticky align-middle">
          <thead>
            <tr>
              <th style="width:64px">#</th>
              <th>Komponen</th>
              <th>Side</th>
              <th class="text-end">Rate</th>
              <th>Jenis</th>
              <th>Basis</th>
              <th class="text-end">Qty</th>
              <th class="text-end">Amount</th>
              <th>Source</th>
              <th>Catatan</th>
            </tr>
          </thead>
          <tbody>
            @php
  $details = $details->sortBy(function ($d) {
      $side = strtolower($d->side ?? data_get($d, 'component.side') ?? '');
      $name = strtolower($d->component_name ?? data_get($d, 'component.name') ?? '');

      return match (true) {
          str_contains($name, 'gaji pokok') || str_contains($name, 'basic') => 0,   // Gaji pokok paling atas
          str_contains($side, 'earning') => 1,                                     // Earning lain
          str_contains($name, 'tunjangan') || str_contains($name, 'allowance') => 2, // Allowance
          str_contains($side, 'deduction') => 3,                                   // Potongan
          default => 9,                                                            // Lain-lain
      };
  })->values();
@endphp

          @forelse($details as $i => $d)
            @php
              $metaArr = is_array($d->meta ?? null) ? $d->meta : (json_decode($d->meta ?? '[]', true) ?: []);
              $side    = $d->side ?? $metaArr['side'] ?? data_get($d, 'component.side');
              $rateVal = $d->rate_value ?? $metaArr['rate_value'] ?? null;
              $unit    = $d->unit ?? $metaArr['unit'] ?? '';
              $qty     = $d->quantity ?? $metaArr['qty'] ?? null;
              $rtype   = $d->rate_type ?? $metaArr['rate_type'] ?? null;
              $basis   = $d->basis ?? $metaArr['basis'] ?? null;
              $source  = $d->source ?? $metaArr['source'] ?? null;
              $note    = $d->note ?? $metaArr['note'] ?? null;
            @endphp
            <tr>
              <td>{{ $i + 1 }}</td>
              <td>
                <div class="fw-semibold">{{ $d->component_name ?? data_get($d, 'component.name') ?? '—' }}</div>
                <div class="small text-muted">{{ $d->component_code ?? data_get($d, 'component.code') ?? '—' }}</div>
              </td>
              <td>
                <span class="badge {{ strtolower((string)$side) === 'deduction' ? 'bg-danger' : 'bg-success' }} badge-side">
                  {{ $toDisplay($side) }}
                </span>
              </td>
              <td class="text-end">
                {{ $toDisplay($rateVal) }}
                @if(!empty($unit))
                  <span class="text-muted small">{{ $toDisplay($unit) }}</span>
                @endif
              </td>
              <td>{{ $toDisplay($rtype) }}</td>
              <td>{{ $toDisplay($basis) }}</td>
              <td class="text-end">
                {{ is_numeric($qty) ? number_format($qty, 2, ',', '.') : $toDisplay($qty) }}
              </td>
              <td class="text-end">Rp {{ number_format($d->amount ?? 0, 0, ',', '.') }}</td>
              <td>{{ $toDisplay($source) }}</td>
              <td class="small">{{ $toDisplay($note) }}</td>
            </tr>
          @empty
            <tr><td colspan="10" class="text-center py-4">Belum ada detail untuk item ini.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
  @if(session('success'))
    <script>window.alerts?.toastSuccess(@json(session('success')));</script>
  @endif
  @if(session('error'))
    <script>window.alerts?.toastError(@json(session('error')));</script>
  @endif
@endpush
