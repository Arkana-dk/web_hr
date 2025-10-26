@extends('layouts.master')

@section('title', 'Pay Runs')

@push('styles')
<style>
  :root{
    --soft-bg: rgba(78,115,223,.10);
    --soft-bd: rgba(78,115,223,.25);
    --muted: #6c757d;
  }

  /* ===== HERO ===== */
  .card-hero{
    background: linear-gradient(135deg,#f7efff 0%, #eef5ff 60%, #ffffff 100%);
    border: 1px solid #eef2ff;
    border-radius: 1rem;
  }
  .hero-stat{
    background: #fff;
    border: 1px solid #eef2ff;
    border-radius: .9rem;
    padding: .85rem 1rem;
    min-width: 160px;
  }
  .hero-stat .label{ font-size:.78rem; color:var(--muted); margin-bottom:.25rem; }
  .hero-stat .value{ font-weight:700; font-size:1.1rem; }

  /* ===== CONTROLS ===== */
  .shadow-soft{ box-shadow: 0 10px 30px -12px rgba(0,0,0,.20); }
  .card-rounded{ border-radius: 1rem; }
  .toolbar{ gap:.5rem } @media (min-width:768px){ .toolbar{ gap:.75rem } }
  .btn-soft-primary{ background: var(--soft-bg); border-color: var(--soft-bd); color:#4e73df; }
  .btn-soft-primary:hover{ background: rgba(78,115,223,.14); }
  .btn-group-density .btn{ border-radius: 999px!important; }

  /* quick tabs */
  .quick-tabs .badge-pill{ border-radius:999px; padding:.5rem .8rem; font-weight:600; letter-spacing:.02em; }
  .quick-tabs .badge-light{ border:1px solid #e9ecef; }

  /* ===== TABLE ===== */
  .table-wrap{ position: relative; }
  .table thead th{ position: sticky; top:0; z-index:2; background:#fff; }
  .table thead.is-stuck th{ box-shadow: 0 2px 0 rgba(0,0,0,.05); }
  .table-hover tbody tr{ transition: background-color .15s ease; }
  .table-nowrap th, .table-nowrap td{ white-space: nowrap; }
  .table-density-comfortable td, .table-density-comfortable th{ padding-top:.9rem; padding-bottom:.9rem; }
  .table-density-compact td, .table-density-compact th{ padding-top:.52rem; padding-bottom:.52rem; font-size:.935rem; }
  tr[data-href]{ cursor:pointer; } tr[data-href]:hover{ background-color: rgba(0,0,0,.03); }
  .text-truncate-1{ max-width: 280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  @media (max-width:576px){ .text-truncate-1{ max-width: 200px; } }

  .empty-illu{ width:72px;height:72px;border-radius:16px;background:linear-gradient(135deg,#e9f2ff,#eef5ff);display:flex;align-items:center;justify-content:center; }
  .status-dot{ display:inline-block; width:.6rem; height:.6rem; border-radius:999px; margin-right:.35rem; vertical-align:middle; }
  .dot-draft{ background:#ffc107; } .dot-simulated{ background:#17a2b8; } .dot-finalized{ background:#28a745; } .dot-locked{ background:#6c757d; }
</style>
@endpush

@section('content')
<div class="container py-4">
  @php
    $density  = request('density', optional(session('ui'))['density'] ?? 'comfortable');
    $tableDensity = $density==='compact' ? 'table-density-compact' : 'table-density-comfortable';

    // ringkasan kecil di hero (siapkan di controller kalau mau lebih akurat)
    $totalAll   = $runs->total();
    $draftCount = $counts['draft']     ?? ($runs->where('status','draft')->count());
    $simCount   = $counts['simulated'] ?? ($runs->where('status','simulated')->count());
    $finCount   = $counts['finalized'] ?? ($runs->where('status','finalized')->count());
    $lockCount  = $counts['locked']    ?? ($runs->where('status','locked')->count());
  @endphp

  {{-- ===== HERO ===== --}}
  <div class="card card-hero shadow-soft card-rounded mb-3">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div class="mb-3 mb-md-0">
          <h4 class="mb-1">Pay Runs</h4>
          <small class="text-muted">Kelola proses penggajian per periode &amp; grup. Klik baris untuk review.</small>
        </div>

        <div class="d-flex flex-wrap align-items-center" style="gap:.65rem">
          @if (Route::has('admin.payruns.create'))
            <a href="{{ route('admin.payruns.create') }}" class="btn btn-primary rounded-pill px-3">
              <i class="fas fa-plus mr-1"></i><span class="d-none d-sm-inline">Buat </span>Pay Run
            </a>
          @endif
          @if (Route::has('admin.payruns.export'))
            <a href="{{ route('admin.payruns.export', request()->all()) }}" class="btn btn-outline-secondary rounded-pill px-3">
              <i class="fas fa-file-export mr-1"></i> Ekspor
            </a>
          @endif
        </div>
      </div>

      <div class="d-flex flex-wrap mt-3" style="gap:.75rem">
        <div class="hero-stat">
          <div class="label">Total Pay Runs</div>
          <div class="value">{{ number_format($totalAll) }}</div>
        </div>
        <div class="hero-stat">
          <div class="label"><span class="status-dot dot-draft"></span>Draft</div>
          <div class="value">{{ number_format($draftCount) }}</div>
        </div>
        <div class="hero-stat">
          <div class="label"><span class="status-dot dot-simulated"></span>Simulated</div>
          <div class="value">{{ number_format($simCount) }}</div>
        </div>
        <div class="hero-stat">
          <div class="label"><span class="status-dot dot-finalized"></span>Finalized</div>
          <div class="value">{{ number_format($finCount) }}</div>
        </div>
        <div class="hero-stat">
          <div class="label"><span class="status-dot dot-locked"></span>Locked</div>
          <div class="value">{{ number_format($lockCount) }}</div>
        </div>
      </div>
    </div>
  </div>

  {{-- ===== TOOLBAR ===== --}}
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 toolbar">
    <div class="quick-tabs d-flex flex-wrap align-items-center" style="gap:.4rem">
      @php $statuses = ['draft'=>'warning','simulated'=>'info','finalized'=>'success','locked'=>'secondary']; @endphp
      <a href="{{ route('admin.payruns.index', array_filter(array_merge(request()->except('page','status'), ['status'=>null]))) }}"
         class="badge badge-pill {{ request('status') ? 'badge-light text-dark' : 'badge-primary' }}">Semua</a>
      @foreach($statuses as $s => $color)
        <a href="{{ request()->fullUrlWithQuery(['status'=>$s, 'page'=>null]) }}"
           class="badge badge-pill badge-{{ $color }} {{ request('status')===$s ? '' : 'opacity-75' }}">{{ ucfirst($s) }}</a>
      @endforeach
    </div>

    <div class="d-flex align-items-center" style="gap:.5rem">
      {{-- Sort --}}
      <div class="dropdown">
        <button class="btn btn-outline-secondary rounded-pill dropdown-toggle" data-toggle="dropdown">
          <i class="fas fa-sort mr-1"></i> Urutkan
        </button>
        @php
          $sort = request('sort','created_desc');
          $opts = [
            'created_desc' => 'Terbaru dibuat',
            'created_asc'  => 'Terlama dibuat',
            'period_desc'  => 'Periode terbaru',
            'period_asc'   => 'Periode terlama',
          ];
        @endphp
        <div class="dropdown-menu dropdown-menu-right">
          @foreach($opts as $k=>$label)
            <a class="dropdown-item {{ $sort===$k ? 'active' : '' }}"
               href="{{ request()->fullUrlWithQuery(['sort'=>$k,'page'=>null]) }}">{{ $label }}</a>
          @endforeach
        </div>
      </div>

      {{-- Density --}}
      <div class="btn-group btn-group-density" role="group" aria-label="Kerapatan tabel">
        <a href="{{ request()->fullUrlWithQuery(['density' => 'comfortable', 'page'=>null]) }}"
           class="btn btn-outline-secondary {{ $density==='comfortable' ? 'active' : '' }}"
           title="Ruang lega" data-toggle="tooltip"><i class="fas fa-grip-lines"></i></a>
        <a href="{{ request()->fullUrlWithQuery(['density' => 'compact', 'page'=>null]) }}"
           class="btn btn-outline-secondary {{ $density==='compact' ? 'active' : '' }}"
           title="Lebih rapat" data-toggle="tooltip"><i class="fas fa-grip-lines-vertical"></i></a>
      </div>
    </div>
  </div>

  {{-- ===== FILTER STRIP (inline form) ===== --}}
  <div class="card border-0 shadow-soft card-rounded mb-3">
    <div class="card-body pb-2">
      <form method="get" class="form-row align-items-center">
        <div class="col-lg-4 col-md-6 col-sm-12 mb-2">
          <input type="search" name="q" value="{{ request('q') }}" class="form-control"
                 placeholder="Cari pay group / catatan…" aria-label="Cari pay group atau catatan">
        </div>
        <div class="col-lg-2 col-md-6 col-sm-12 mb-2">
          <select name="group" class="custom-select" aria-label="Filter berdasarkan grup">
            <option value="">Semua grup</option>
            @foreach(($groups ?? []) as $g)
              <option value="{{ $g->id }}" @selected(request('group')==$g->id)>{{ $g->name }}</option>
            @endforeach
          </select>
        </div>

        {{-- Periode ringkas (opsional: sediakan di controller) --}}
        <div class="col-lg-3 col-md-6 col-sm-12 mb-2">
          <input type="month" name="period" value="{{ request('period') }}" class="form-control" placeholder="Periode (YYYY-MM)">
        </div>

        <div class="col-lg-3 col-md-6 col-sm-12 d-flex mb-2">
          <button class="btn btn-outline-secondary rounded-pill mr-2" type="submit">
            <i class="fas fa-filter mr-1"></i> Terapkan
          </button>
          <a href="{{ route('admin.payruns.index') }}" class="btn btn-outline-secondary rounded-pill"
             data-confirm="Reset semua filter?" data-confirm-text="Semua isian akan dikosongkan.">Reset</a>
        </div>
      </form>
    </div>
  </div>

  {{-- ===== TABLE ===== --}}
  <div class="card border-0 shadow-soft card-rounded">
    <div class="table-responsive table-wrap">
      <table class="table table-striped table-hover align-middle mb-0 table-nowrap {{ $tableDensity }}">
        <thead class="thead-light">
          <tr>
            {{-- (Opsional) bulk select: uncomment kalau butuh --}}
            {{-- <th style="width:3%"><input type="checkbox" id="chk-all"></th> --}}
            <th style="width:5%">#</th>
            <th style="width:28%">Pay Group</th>
            <th style="width:24%">Periode</th>
            <th style="width:14%">Status</th>
            <th style="width:17%">Finalized</th> 
            <th style="width:17%">Dibuat</th>
            <th class="text-right" style="width:12%">Aksi</th>
          </tr>
        </thead>
<tbody>
@forelse($runs as $i => $run)
  @php
    $badgeMap = [
      'draft'     => 'badge-warning text-dark',
      'simulated' => 'badge-info text-dark',
      'finalized' => 'badge-success',
      'locked'    => 'badge-secondary',
    ];
    $badgeClass = $badgeMap[$run->status] ?? 'badge-light text-dark';
    $pg = optional($run->payGroup);
  @endphp
  <tr data-href="{{ route('admin.payruns.review', $run) }}">
    {{-- <td><input type="checkbox" class="chk-row" value="{{ $run->id }}"></td> --}}
    <td>{{ $runs->firstItem() + $i }}</td>

    <td>
      <div class="font-weight-semibold text-truncate-1" title="{{ $pg->name ?? '-' }}">{{ $pg->name ?? '-' }}</div>
      @if($pg?->code)
        <div class="small text-muted text-truncate-1" title="{{ $pg->code }}">{{ $pg->code }}</div>
      @endif
    </td>

    <td>
      {{ \Illuminate\Support\Carbon::parse($run->start_date)->format('Y-m-d') }}
      <span class="text-muted">s/d</span>
      {{ \Illuminate\Support\Carbon::parse($run->end_date)->format('Y-m-d') }}
    </td>

    <td>
      <span class="badge badge-pill {{ $badgeClass }}">
        {{ strtoupper($run->status) }}
      </span>
    </td>

    {{-- ⬇️ KOLOM BARU: Finalized (nama + waktu) --}}
    <td>
      @if($run->locked_at || $run->finalized_at)
        <div class="font-weight-semibold">
          {{ optional($run->lockedByUser)->name ?? '—' }}
        </div>
        <div class="small text-muted">
          {{ optional($run->finalized_at ?? $run->locked_at)->format('Y-m-d H:i') }}
        </div>
      @else
        <span class="text-muted">—</span>
      @endif
    </td>

    <td>
      <span title="{{ \Illuminate\Support\Carbon::parse($run->created_at)->toDayDateTimeString() }}">
        {{ \Illuminate\Support\Carbon::parse($run->created_at)->format('Y-m-d H:i') }}
      </span>
      @if($run->updated_at && $run->updated_at->gt($run->created_at))
        <div class="small text-muted" title="Terakhir diperbarui {{ $run->updated_at->toDayDateTimeString() }}">
          upd: {{ $run->updated_at->format('Y-m-d H:i') }}
        </div>
      @endif
    </td>

    <td class="text-right">
      <div class="btn-group">
        <a class="btn btn-sm btn-primary rounded-pill" href="{{ route('admin.payruns.review', $run) }}">
          <i class="fas fa-eye mr-1"></i> Review
        </a>

        {{-- Tampilkan tombol lanjutan berdasar status (pakai POST) --}}
        @if($run->status === 'draft' && Route::has('admin.payruns.simulate'))
          <form method="POST" action="{{ route('admin.payruns.simulate', $run) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-info rounded-pill"
                    data-confirm
                    data-confirm-text="Hitung ulang item pay run untuk periode ini?">
              <i class="fas fa-magic mr-1"></i> Simulate
            </button>
          </form>
        @endif

        @if($run->status === 'simulated' && Route::has('admin.payruns.finalize'))
          <form method="POST" action="{{ route('admin.payruns.finalize', $run) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-success rounded-pill"
                    data-confirm
                    data-confirm-text="Finalisasi pay run ini? Setelah final, data akan dikunci.">
              <i class="fas fa-check mr-1"></i> Finalize
            </button>
          </form>
        @endif
      </div>
    </td>
  </tr>
@empty
  <tr>
    <td colspan="7">
      <div class="d-flex align-items-center justify-content-center py-5 text-center flex-column" style="gap:12px;">
        <div class="empty-illu"><i class="fas fa-table text-primary fa-lg"></i></div>
        <div>
          <div class="font-weight-semibold">Belum ada pay run</div>
          <div class="text-muted">Mulai dengan membuat pay run pertama Anda.</div>
        </div>
        @if (Route::has('admin.payruns.create'))
          <a href="{{ route('admin.payruns.create') }}" class="btn btn-primary rounded-pill px-3">
            <i class="fas fa-plus mr-1"></i> Buat Pay Run
          </a>
        @endif
      </div>
    </td>
  </tr>
@endforelse
</tbody>

      </table>
    </div>

    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center flex-wrap">
      <small class="text-muted mb-2 mb-sm-0">
        Menampilkan {{ $runs->firstItem() ?? 0 }}–{{ $runs->lastItem() ?? 0 }} dari {{ $runs->total() }} data
        @if($runs->count())
          • Halaman {{ $runs->currentPage() }} / {{ $runs->lastPage() }}
        @endif
      </small>
      {{ $runs->withQueryString()->links() }}
    </div>
  </div>
</div> {{-- /container --}}
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  $(function () {
    // Tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Confirm handler (reuse)
    $(document).on('click','[data-confirm]',function(e){
      e.preventDefault();
      const el = this;
      Swal.fire({
        icon:'question', title:$(el).data('confirm')||'Yakin?', text:$(el).data('confirm-text')||'',
        showCancelButton:true, confirmButtonText: $(el).data('confirm-yes')||'Ya',
        cancelButtonText: $(el).data('confirm-no')||'Batal', reverseButtons:true
      }).then(res=>{
        if(!res.isConfirmed) return;
        if(el.tagName==='A' && el.href){ window.location.href = el.href; return; }
        const formId = $(el).data('submit'); const form = formId ? document.getElementById(formId) : $(el).closest('form')[0];
        if(form) form.submit();
      });
    });

    // Clickable rows (ignore control clicks)
    $('tr[data-href]').on('click', function(e){
      if($(e.target).closest('a,button,input,select,label,.dropdown-menu').length) return;
      window.location.href = this.dataset.href;
    });

    // Sticky header subtle shadow
    const wrap = document.querySelector('.table-wrap');
    if (wrap) {
      const thead = wrap.querySelector('thead');
      wrap.addEventListener('scroll', () => { if (thead) thead.classList.toggle('is-stuck', wrap.scrollTop > 0); });
    }

    // (Opsional) bulk select
    // $('#chk-all').on('change', function(){ $('.chk-row').prop('checked', this.checked); });
  });
</script>
@endpush
