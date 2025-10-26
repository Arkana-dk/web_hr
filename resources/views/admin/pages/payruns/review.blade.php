@extends('layouts.master')

@section('title', 'Review Pay Run')

@push('styles')
<style>
  /* ===== Actions col spacing ===== */
  .table-actions-col{ width: 300px; } /* boleh ubah ke 280–320 sesuai kebutuhan */

  .status-group{
    display:flex; flex-wrap:wrap; align-items:center;
    gap: clamp(.45rem, 1.2vw, 1rem); /* fleksibel: mobile rapat, desktop lega */
  }
  .status-badge{ padding:.35rem .6rem; border-radius:999px; line-height:1; }
  .btn-pill.btn-sm{ padding:.35rem .7rem; line-height:1; } /* tinggi selaras badge */

  :root{
    --hero-grad: linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%);
    --chip-bg: rgba(13,110,253,.08);
    --chip-bd: rgba(13,110,253,.2);
  }

  /* ===== HERO ===== */
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
  .table-responsive{ width:100%; }
  .table-nowrap th, .table-nowrap td{ white-space:nowrap; }

  /* ===== BUTTONS ===== */
  .btn-pill{ border-radius: 999px; }
  .btn-elev{ box-shadow: 0 8px 24px -10px rgba(13,110,253,.6); }
  .btn-soft-primary{
    background: var(--chip-bg);
    border-color: var(--chip-bd);
    color:#0d6efd;
  }
  .btn-soft-primary:hover{ background: rgba(13,110,253,.12); }
  .btn-soft-white{
    background: rgba(255,255,255,.15);
    border-color: rgba(255,255,255,.25);
    color: #fff;
  }
  .btn-soft-white:hover{ background: rgba(255,255,255,.25); color:#fff; }

  /* ===== SUMMARY ===== */
  .stat-card{ border: 0; border-radius: 1rem; background: transparent; }
  .summary-grid{ display:grid; grid-template-columns:1fr; gap:.75rem }
  @media (min-width:576px){ .summary-grid{ grid-template-columns:repeat(3,minmax(0,1fr)); } }
  .summary-item{ background:transparent; border-radius:.9rem; padding:.25rem .5rem; }
  .summary-item .label{ font-size:.8rem; color:#6c757d; margin-bottom:0 }
  .summary-item .value{ font-weight:600; font-size: 1.1rem; font-variant-numeric: tabular-nums; text-align:right; color: #343a40; }

  /* ===== TABLE ===== */
  .table-sticky thead th{ position:sticky; top:0; background:#fff; z-index:2; }
    .status-badge{
    text-transform: uppercase;
    letter-spacing: .02em;
    color: #fff !important; /* Tambahan baru */
  }
  .table thead th{ border-top:0; }

  .table-hover tbody tr{ transition: background-color .12s ease; }

  /* ===== FILTER CARD (mini-hero) ===== */
  .filter-card{
    border: 0;
    border-radius: 1.25rem;
    background:
      linear-gradient(#ffffff,#ffffff) padding-box,
      linear-gradient(135deg, rgba(13,110,253,.35), rgba(111,66,193,.35)) border-box;
    border: 1px solid transparent;
    box-shadow: 0 10px 30px -18px rgba(13,110,253,.45);
  }
  .filter-card .card-body{ padding: .9rem 1rem; }
  @media (min-width: 768px){ .filter-card .card-body{ padding: 1.05rem 1.25rem; } }

  /* Pill controls */
  .pill .form-control,
  .pill .form-select{
    border-radius: 999px;
    height: 44px;
    padding-left: 14px;
    padding-right: 14px;
  }
  .pill .form-select{ padding-right: 36px; } /* ruang icon caret */

  /* Label kecil seragam */
  .filter-label{ font-size:.78rem; color:#6c757d; margin:0 0 .25rem }

  /* Toolbar spacing (jarak antar tombol) */
  .toolbar{ display:flex; gap:.5rem }
  @media (min-width: 576px){ .toolbar{ gap:.75rem } }

  /* Biar tombol “Reset” tidak menempel */
  .toolbar .btn{ min-height: 44px; }

  /* Kompak di mobile, rapi di desktop */
  .filter-grid{
    display:grid;
    grid-template-columns: 1fr;
    gap: .75rem;
    width:100%;
  }
  @media (min-width: 768px){
    .filter-grid{
      grid-template-columns: 1.2fr .8fr auto;
      align-items: end;
    }
  }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

  @php
    $fmt = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
    $statusClass = [
      'draft'     => 'bg-secondary',
      'simulated' => 'bg-info',
      'finalized' => 'bg-primary',
      'locked'    => 'bg-dark',
    ][$payrun->status] ?? 'bg-secondary';

    $pageGross = $items->sum('gross_earnings');
    $pageDed   = $items->sum('total_deductions');
    $pageNet   = $items->sum('net_pay');
  @endphp

  {{-- ========= HERO ========= --}}
  <div class="card hero-card shadow-sm mb-3">
    <div class="hero-body d-flex flex-column gap-3 gap-md-0 flex-md-row justify-content-between align-items-md-center">
      <div>
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="tag-soft">Pay Run #{{ $payrun->id }}</span>
          <span class="badge status-badge {{ $statusClass }}">{{ $payrun->status }}</span>
        </div>
        <h2 class="hero-title mb-1">Review Pay Run</h2>
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
          <span class="tag-soft"><i class="bi bi-people me-1"></i> {{ $totalEmployees }} Karyawan</span>
          <span class="tag-soft"><i class="bi bi-list-check me-1"></i> {{ $items->total() }} Item</span>
          <span class="tag-soft"><i class="bi bi-cash-coin me-1"></i> Halaman ini: {{ $fmt($pageNet) }}</span>
        </div>
      </div>

      {{-- ACTIONS pill --}}
      <div class="d-flex flex-wrap gap-2">

        {{-- Simulate --}}
        <form method="POST" action="{{ route('admin.payruns.simulate', $payrun) }}" class="js-confirmable"
              data-title="Jalankan Simulasi?" data-text="Ini akan menghitung ulang item pay run. Lanjutkan?">
          @csrf
          <button class="btn btn-success btn-pill btn-elev" {{ $payrun->locked_at ? 'disabled' : '' }}>
            <i class="bi bi-cpu me-1"></i> Simulate
          </button>
        </form>

        @php $hasItems = $items->total() > 0; @endphp

        {{-- Finalize (draft/simulated, belum locked) --}}
        @if(in_array($payrun->status, ['draft','simulated']) && !$payrun->locked_at)
          <form method="POST" action="{{ route('admin.payruns.finalize', $payrun) }}" class="js-confirmable"
                data-title="Finalize Pay Run?" data-text="Setelah final, data item akan dikunci. Lanjutkan?">
            @csrf
            <button class="btn btn-primary btn-pill" {{ $hasItems ? '' : 'disabled' }}>
              <i class="bi bi-check2-circle me-1"></i> Finalize
            </button>
          </form>
        @endif

        {{-- Unlock (reopen) hanya jika sudah locked --}}
        @if($payrun->locked_at)
          <form method="POST" action="{{ route('admin.payruns.reopen', $payrun) }}" class="js-confirmable"
                data-title="Unlock Pay Run?" data-text="Status akan kembali ke draft.">
            @csrf
            <button class="btn btn-outline-secondary btn-pill">
              <i class="bi bi-unlock me-1"></i> Unlock
            </button>
          </form>
        @endif

        <a href="{{ route('admin.payruns.index') }}" class="btn btn-soft-white btn-pill">
          <i class="bi bi-arrow-left-short me-1"></i> Kembali
        </a>
      </div>
    </div>
  </div>

  {{-- Info locked --}}
  @if(!empty($payrun->locked_at))
    <div class="alert alert-warning d-flex align-items-center gap-2">
      <i class="bi bi-lock-fill"></i> Pay run ini <strong>LOCKED</strong>. Simulasi & perubahan diblok.
    </div>
  @endif

  {{-- ===== SUMMARY STRIP ===== --}}
  <div class="row g-3 mb-3 align-items-stretch">
    <div class="col-12 col-md-3">
      <div class="d-flex flex-column gap-3 h-100">
        <div class="card stat-card shadow-sm">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <div class="text-muted small">Karyawan di Pay Group</div>
              <div class="fs-5 fw-semibold">{{ $totalEmployees }}</div>
            </div>
            <i class="bi bi-people fs-3 text-secondary"></i>
          </div>
        </div>
        <div class="card stat-card shadow-sm">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <div class="text-muted small">Items (halaman / total)</div>
              <div class="fs-5 fw-semibold">{{ $items->count() }} / {{ $items->total() }}</div>
            </div>
            <i class="bi bi-list-check fs-3 text-secondary"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-9">
      <div class="card stat-card shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-2">Ringkas (halaman ini)</div>
          <div class="summary-grid">
            <div class="summary-item">
              <div class="label">Gross</div>
              <div class="value">{{ $fmt($pageGross) }}</div>
            </div>
            <div class="summary-item">
              <div class="label">Deduction</div>
              <div class="value">{{ $fmt($pageDed) }}</div>
            </div>
            <div class="summary-item">
              <div class="label">Net</div>
              <div class="value">{{ $fmt($pageNet) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ===== FILTER (mini-hero) ===== --}}
  <form action={{ request()->url() }} method="GET" class="card filter-card mb-3">
    <div class="card-body">
      <div class="filter-grid pill">
        <div>
          <label class="filter-label">Cari karyawan</label>
          <input type="text" name="q" class="form-control"
                 value="{{ request('q') }}" placeholder="Nama karyawan…">
        </div>

        <div>
          <label class="filter-label">Status item</label>
          <select name="status" class="form-select">
            <option value="">— Semua —</option>
            @foreach(['ok'=>'OK','warning'=>'Warning','error'=>'Error'] as $k=>$v)
              <option value="{{ $k }}" {{ request('status')===$k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
          </select>
        </div>

        <div class="ms-md-auto">
          <div class="toolbar">
            <button type="submit" class="btn btn-primary btn-pill">
              <i class="bi bi-sliders me-1"></i> Terapkan
            </button>
            <a href="{{ request()->url() }}" class="btn btn-outline-secondary btn-pill">Reset</a>
          </div>
        </div>
      </div>
    </div>
  </form>

  {{-- ===== TABLE ===== --}}
  <div class="card shadow rounded-4">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 table-sticky align-middle w-100 table-nowrap">
          <thead>
            <tr>
              <th style="width:64px">#</th>
              <th>Karyawan</th>
              <th class="text-end">Gross</th>
              <th class="text-end">Deduction</th>
              <th class="text-end">Net</th>
              <th class="table-actions-col">Aksi / Status</th>
            </tr>
          </thead>
          <tbody>
          @forelse($items as $i => $it)
            @php
              $badge = $it->result_status === 'ok' ? 'bg-success'
                       : ($it->result_status === 'warning' ? 'bg-warning text-dark' : 'bg-danger');
              $diag = $it->diagnostics ?? null;
              if (is_string($diag)) { $parsed = json_decode($diag, true); $diag = $parsed ?? $diag; }
              $hasDiag = !empty($diag);
            @endphp
            <tr>
              <td>{{ $items->firstItem() + $i }}</td>
              <td>
                @php $emp = optional($it->employee); @endphp

                <div class="fw-semibold">{{ $emp->name ?? '—' }}</div>
                <div class="small text-muted">
                  No: <strong>{{ $emp->employee_number ?? '—' }}</strong>
                </div>
                <div class="small text-muted">
                  {{ optional($emp->department)->name ?? '—' }} /
                  {{ optional($emp->section)->name ?? '—' }} /
                  {{ optional($emp->position)->name ?? '—' }}
                </div>

                @if(isset($it->paid_days, $it->scheduled_days))
                  <div class="small text-muted">Paid: {{ $it->paid_days }}/{{ $it->scheduled_days }} hari</div>
                @endif
                @if(!empty($it->daily_basic_rate))
                  <div class="small text-muted">Daily BASIC: <strong>{{ $fmt($it->daily_basic_rate) }}</strong></div>
                @endif
              </td>

              <td class="text-end">{{ $fmt($it->gross_earnings) }}</td>
              <td class="text-end">{{ $fmt($it->total_deductions) }}</td>
              <td class="text-end"><strong>{{ $fmt($it->net_pay) }}</strong></td>
              <td>
                <div class="status-group">
                  <span class="badge status-badge {{ $badge }}">{{ $it->result_status }}</span>

                  {{-- Lihat Detail item --}}
                  <a href="{{ route('admin.payruns.items.show', [$payrun, $it]) }}"
                     class="btn btn-sm btn-outline-primary btn-pill">
                    Lihat Detail
                  </a>

                  @if($hasDiag)
                    <button class="btn btn-sm btn-outline-secondary btn-pill" type="button"
                            data-bs-toggle="collapse" data-bs-target="#diag-{{ $it->id }}"
                            aria-expanded="false" aria-controls="diag-{{ $it->id }}">
                      Detail
                    </button>
                  @endif
                </div>
              </td>
            </tr>
            @if($hasDiag)
              <tr class="collapse" id="diag-{{ $it->id }}">
                <td colspan="6">
                  <pre class="mb-0 small">{{ is_array($diag) ? implode("\n", $diag) : $diag }}</pre>
                </td>
              </tr>
            @endif
          @empty
            <tr><td colspan="6" class="text-center py-4">Belum ada item. Klik <strong>Simulate</strong>.</td></tr>
          @endforelse
          </tbody>

          @if($items->count())
          <tfoot>
            <tr class="table-light">
              <th colspan="2" class="text-end">Total (halaman ini):</th>
              <th class="text-end">{{ $fmt($pageGross) }}</th>
              <th class="text-end">{{ $fmt($pageDed) }}</th>
              <th class="text-end">{{ $fmt($pageNet) }}</th>
              <th></th>
            </tr>
          </tfoot>
          @endif
        </table>
      </div>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <div class="small text-muted">
        Menampilkan {{ $items->firstItem() }}–{{ $items->lastItem() }} dari {{ $items->total() }} item
      </div>
      {{ $items->withQueryString()->links() }}
    </div>
  </div>
</div>
@endsection


@push('scripts')
  {{-- SweetAlert2 --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
  (function () {
    // Ambil flash & error bag dari Laravel
    const flashSuccess = @json(session('success'));
    const flashError   = @json(session('error'));
    const errorBag     = @json($errors->all());

    // Tampilkan validation errors (jika ada)
    if (Array.isArray(errorBag) && errorBag.length) {
      const html = '<ul style="text-align:left;margin:0;padding-left:1.2rem;">'
                   + errorBag.map(e => `<li>${e}</li>`).join('')
                   + '</ul>';
      Swal.fire({ icon: 'error', title: 'Gagal', html });
    }

    // Tampilkan flash error/success
    if (flashError) {
      Swal.fire({ icon: 'error', title: 'Error', text: flashError });
    }
    if (flashSuccess) {
      Swal.fire({ icon: 'success', title: 'Berhasil', text: flashSuccess });
    }

    // Konfirmasi aksi pada form yang punya class .js-confirmable
    document.addEventListener('click', function (e) {
      const btn  = e.target.closest('.js-confirmable button, [data-confirm]');
      if (!btn) return;
      const form = btn.closest('form');
      if (!form) return;

      e.preventDefault();

      const title = form.dataset.title || btn.dataset.title || 'Yakin?';
      const text  = form.dataset.text  || btn.dataset.text  || '';

      Swal.fire({
        icon: 'warning',
        title,
        text,
        showCancelButton: true,
        confirmButtonText: 'Ya, lanjutkan',
        cancelButtonText: 'Batal'
      }).then((res) => {
        if (res.isConfirmed) form.submit();
      });
    });
  })();
  </script>

  {{-- (Opsional) tetap pertahankan toast custom-mu --}}
  @if(session('success'))
    <script>window.alerts?.toastSuccess(@json(session('success')));</script>
  @endif
  @if(session('error'))
    <script>window.alerts?.toastError(@json(session('error')));</script>
  @endif
@endpush
