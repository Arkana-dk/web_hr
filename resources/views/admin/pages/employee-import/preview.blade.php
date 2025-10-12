@extends('layouts.master')

@section('title', 'Preview Data Pegawai dari Excel')

@push('styles')
<style>
  /* ===== Page Layout ===== */
  .hero-card {
    background: linear-gradient(135deg, #0d6efd 0%, #4f46e5 50%, #6f42c1 100%);
    color: #fff;
    border-radius: 1.25rem;
    padding: 1.5rem 1.75rem;
    box-shadow: 0 10px 24px rgba(13,110,253,.25);
    position: relative;
    overflow: hidden;
  }
  .hero-card .hero-badge {
    background: rgba(255,255,255,.15);
    color: #fff;
    padding: .35rem .65rem;
    border-radius: 9999px;
    font-size: .75rem;
    border: 1px solid rgba(255,255,255,.25);
  }
  .hero-card .hero-title { font-weight: 700; letter-spacing: .2px; }
  .hero-card .hero-sub { opacity: .9; }
  .hero-actions .btn { box-shadow: 0 6px 18px rgba(0,0,0,.12); }

  .btn-pill { border-radius: 9999px !important; padding-inline: 1rem; }
  .btn-ghost-light { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.22); }
  .btn-ghost-light:hover { background: rgba(255,255,255,.2); color: #fff; }

  .toolbar { gap: .75rem; }
  @media (max-width: 576px) { .toolbar { flex-direction: column; align-items: stretch; } }

  /* ===== Table Region ===== */
  .table-host-card { background: #fff; border-radius: 1rem; box-shadow: 0 6px 24px rgba(16, 24, 40, .06); }
  .table-wrapper { max-height: 72vh; overflow: auto; border-radius: 1rem; }
  .table-sticky thead th { position: sticky; top: 0; z-index: 3; background: #0d6efd; color: #fff; }

  /* Make table comfortably wide */
  #preview-table { min-width: 1500px; }
  #preview-table th, #preview-table td { vertical-align: middle; }

  /* Freeze first column */
  .freeze-left { position: sticky; left: 0; z-index: 2; background: #fff; }
  .table-sticky thead th.freeze-left { z-index: 4; background: #0d6efd; }

  /* Row states */
  .row-hard { background: rgba(220,53,69,.11) !important; }
  .row-warn { background: rgba(255,193,7,.14) !important; }

  /* Advanced columns toggle */
  .col-advanced { display: none; }
  .show-advanced .col-advanced { display: table-cell; }

  /* Badges & chips */
  .badge-soft-danger { background: rgba(220,53,69,.12); color: #dc3545; }
  .badge-soft-success { background: rgba(25,135,84,.12); color: #198754; }
  .metric-chip { display:inline-flex; align-items:center; gap:.4rem; padding:.35rem .6rem; border-radius:9999px; font-weight:600; font-size:.8rem; }
  .metric-chip.success { background: rgba(25,135,84,.12); color:#198754; }
  .metric-chip.warning { background: rgba(255,193,7,.18); color:#8a6d00; }
  .metric-chip.danger  { background: rgba(220,53,69,.12); color:#dc3545; }
  .metric-chip.neutral { background: rgba(108,117,125,.15); color:#6c757d; }

  /* Inputs */
  .input-chip { border-radius: 9999px; padding-left: .9rem; }
  .input-chip .input-group-text { border-top-left-radius: 9999px; border-bottom-left-radius: 9999px; }

  /* Little blink for focus */
  @keyframes blink { 50% { transform: scale(1.06); } }
  .blink { animation: blink .6s ease-in-out 2; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4"><!-- container-fluid to widen -->

  {{-- ===== Hero / Summary ===== --}}
  @php
    $hasHard = !empty($hardErrors);
    $hasWarn = !empty($softWarnings);
  @endphp

  <div class="hero-card mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="hero-badge">Import</span>
          <span class="hero-badge">Preview</span>
        </div>
        <h3 class="hero-title mb-1">Preview Data Pegawai dari Excel</h3>
        <div class="hero-sub small">
          Total baris di halaman ini: <strong>{{ $rows->count() }}</strong> • Total keseluruhan: <strong>{{ $rows->total() }}</strong> • Halaman: <strong>{{ $rows->currentPage() }}</strong>/<strong>{{ $rows->lastPage() }}</strong>
        </div>
        <div class="mt-2 d-flex flex-wrap gap-2">
          <span class="metric-chip neutral">Baris: {{ $rows->total() }}</span>
          <span class="metric-chip {{ $hasHard ? 'danger' : 'success' }}">Hard: {{ count($hardErrors ?? []) }}</span>
          <span class="metric-chip {{ $hasWarn ? 'warning' : 'neutral' }}">Warn: {{ count($softWarnings ?? []) }}</span>
        </div>
      </div>

      <div class="hero-actions d-flex flex-wrap align-items-center gap-2">
        <a href="{{ route('admin.employee.import.form') }}" class="btn btn-ghost-light btn-pill">
          <i class="fas fa-arrow-left me-1"></i> Kembali Upload
        </a>
        @if (!$hasHard)
          <button form="preview-save-form" type="submit" class="btn btn-light btn-pill text-primary">
            <i class="fas fa-save me-1"></i> {{ $hasWarn ? 'Simpan (Ada Peringatan)' : 'Simpan Semua Data' }}
          </button>
        @else
          <button type="button" class="btn btn-outline-light btn-pill" disabled title="Perbaiki hard error dulu">
            <i class="fas fa-ban me-1"></i> Simpan Dinonaktifkan
          </button>
        @endif
      </div>
    </div>
  </div>

  {{-- ===== Alerts ===== --}}
  @if ($hasHard)
    <div class="alert alert-danger d-flex align-items-start justify-content-between flex-wrap gap-2">
      <div>
        <strong class="me-2">❗ Ada masalah kritikal pada beberapa baris (penyimpanan dinonaktifkan).</strong>
        <span class="badge badge-soft-danger rounded-pill">{{ count($hardErrors) }} baris</span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button id="btn-jump-first" class="btn btn-outline-danger btn-pill">
          <i class="fas fa-location-arrow me-1"></i> Lompat ke baris bermasalah pertama
        </button>
      </div>
      <details class="mt-1 w-100">
        <summary style="cursor:pointer">Lihat detail hard errors</summary>
        <ul class="mb-0 mt-2">
          @foreach ($hardErrors as $rowNum => $msgs)
            <li><strong>Baris {{ $rowNum }}</strong>
              <ul class="mb-2">
                @foreach ($msgs as $m)
                  <li>{!! $m !!}</li>
                @endforeach
              </ul>
            </li>
          @endforeach
        </ul>
      </details>
    </div>
  @endif

  @if ($hasWarn)
    <div class="alert alert-warning">
      <div class="d-flex align-items-center gap-2 mb-1">
        <strong class="me-2">⚠️ Ada data kosong/tidak lengkap. Tetap bisa disimpan, tapi harap dilengkapi setelah import.</strong>
        <span class="badge bg-warning text-dark rounded-pill">{{ count($softWarnings) }} baris</span>
      </div>
      <details class="mt-1">
        <summary style="cursor:pointer">Lihat detail peringatan</summary>
        <ul class="mb-0 mt-2">
          @foreach ($softWarnings as $rowNum => $msgs)
            <li><strong>Baris {{ $rowNum }}</strong>
              <ul class="mb-2">
                @foreach ($msgs as $m)
                  <li>{!! $m !!}</li>
                @endforeach
              </ul>
            </li>
          @endforeach
        </ul>
      </details>
    </div>
  @elseif(!$hasHard)
    <div class="alert alert-success d-flex align-items-center justify-content-between">
      <div><strong>✅ Data siap disimpan.</strong> Tidak ada peringatan.</div>
      <span class="badge badge-soft-success rounded-pill">Clean</span>
    </div>
  @endif

  {{-- ===== Secondary Toolbar ===== --}}
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 toolbar">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <div class="input-group input-group-sm input-chip" style="min-width: 320px;">
        <span class="input-group-text"><i class="fas fa-search"></i></span>
        <input id="quick-filter" type="text" class="form-control" placeholder="Filter nama / email / departemen / posisi (client-side)">
      </div>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="toggle-advanced">
        <label class="form-check-label small" for="toggle-advanced">Tampilkan kolom lanjutan</label>
      </div>
    </div>

    <form method="GET" class="d-flex align-items-center">
      @foreach(request()->except('per_page') as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
      @endforeach
      <label class="me-2 small text-muted">Tampil</label>
      <select name="per_page" class="form-select form-select-sm me-2 btn-pill" onchange="this.form.submit()" style="min-width:110px">
        @foreach([25,50,100,200,300] as $opt)
          <option value="{{ $opt }}" {{ (int)$perPage === $opt ? 'selected' : '' }}>{{ $opt }}</option>
        @endforeach
      </select>
      <span class="small text-muted">baris / halaman</span>
    </form>
  </div>

  {{-- ===== Table Card ===== --}}
  <div class="table-host-card">
    <div id="table-host" class="table-wrapper">
      <table id="preview-table" class="table table-bordered table-hover align-middle table-sticky mb-0">
        <thead>
          <tr>
            <th class="text-center freeze-left" style="width:64px">No</th>
            {{-- Basic columns --}}
            <th>Nama</th>
            <th>Email</th>
            <th>Gender</th>
            <th>Status Nikah</th>
            <th>Tanggungan</th>
            <th>Telepon</th>
            <th>Tgl Lahir</th>
            <th>Departemen</th>
            <th>Section</th>
            <th>Posisi</th>
            <th>Pay Group</th>
            <th>Gaji Diharapkan</th>
            {{-- Advanced columns --}}
            <th class="col-advanced">NIK</th>
            <th class="col-advanced">No. Karyawan</th>
            <th class="col-advanced">TMT</th>
            <th class="col-advanced">Kontrak Selesai</th>
            <th class="col-advanced">Bank</th>
            <th class="col-advanced">Nama Rekening</th>
            <th class="col-advanced">No Rekening</th>
            <th class="col-advanced">Agama</th>
            <th class="col-advanced">Pendidikan</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($rows as $i => $row)
            @php
              $displayRowNum = $rows->firstItem() + $i;       // nomor tampilan
              $key = $row['row_num'] ?? $displayRowNum;       // kunci error/warn
              $rowHard = isset($hardErrors[$key]);
              $rowWarn = isset($softWarnings[$key]);
              $fmt = fn($n) => $n !== null && $n !== '' ? 'Rp '.number_format((float)$n, 0, ',', '.') : '-';
              $fmtDate = function($d) {
                try { return $d ? \Carbon\Carbon::parse($d)->format('Y-m-d') : '-'; }
                catch (\Throwable $e) { return (string)($d ?? '-'); }
              };
            @endphp
            <tr class="{{ $rowHard ? 'row-hard' : ($rowWarn ? 'row-warn' : '') }}">
              <td class="text-center fw-bold freeze-left">
                {{ $displayRowNum }}
                @if($rowHard)
                  <span class="badge bg-danger ms-1 issue-badge" data-row="{{ $key }}" title="Detail hard error">!</span>
                @elseif($rowWarn)
                  <span class="badge bg-warning text-dark ms-1 issue-badge" data-row="{{ $key }}" title="Detail peringatan">!</span>
                @endif
              </td>
              <td>{{ $row['name'] ?? '-' }}</td>
              <td>{{ $row['email'] ?? '-' }}</td>
              <td>{{ $row['gender'] ?? '-' }}</td>
              <td>{{ $row['marital_status'] ?? '-' }}</td>
              <td>{{ isset($row['dependents_count']) ? (int)$row['dependents_count'] : '-' }}</td>
              <td>{{ $row['phone_number'] ?? '-' }}</td>
              <td>{{ $fmtDate($row['birth_date'] ?? null) }}</td>
              <td>{{ $row['department_name'] ?? ($row['department_id'] ?? '-') }}</td>
              <td>{{ $row['section_name'] ?? ($row['section_id'] ?? '-') }}</td>
              <td>{{ $row['position_name'] ?? ($row['position_id'] ?? '-') }}</td>
              <td>{{ $row['pay_group_code'] ?? ($row['pay_group_name'] ?? ($row['pay_group_id'] ?? '-')) }}</td>
              <td>{{ $fmt($row['expected_salary'] ?? null) }}</td>
              <td class="col-advanced">{{ $row['national_identity_number'] ?? '-' }}</td>
              <td class="col-advanced">{{ $row['employee_number'] ?? '-' }}</td>
              <td class="col-advanced">{{ $fmtDate($row['tmt'] ?? null) }}</td>
              <td class="col-advanced">{{ $fmtDate($row['contract_end_date'] ?? null) }}</td>
              <td class="col-advanced">{{ $row['bank_name'] ?? '-' }}</td>
              <td class="col-advanced">{{ $row['bank_account_name'] ?? '-' }}</td>
              <td class="col-advanced">{{ $row['bank_account_number'] ?? '-' }}</td>
              <td class="col-advanced">{{ $row['religion'] ?? '-' }}</td>
              <td class="col-advanced">{{ $row['last_education'] ?? $row['education'] ?? '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="22" class="text-center text-muted py-4">Tidak ada data ditemukan.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination + actions footer --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-3">
      <div class="small text-muted">
        Menampilkan <strong>{{ $rows->firstItem() }}</strong>–<strong>{{ $rows->lastItem() }}</strong> dari <strong>{{ $rows->total() }}</strong> baris
      </div>
      <div>
        {{ $rows->withQueryString()->onEachSide(1)->links() }}
      </div>
    </div>
  </div>

  {{-- ===== Actions (bottom persistent) ===== --}}
  <div class="d-flex flex-wrap gap-2 mt-3">
    <a href="{{ route('admin.employee.import.form') }}" class="btn btn-outline-secondary btn-pill">
      <i class="fas fa-arrow-left me-1"></i> Kembali Upload
    </a>
    @if (!$hasHard)
      <button id="btn-save" form="preview-save-form" type="submit" class="btn btn-success btn-pill">
        <i class="fas fa-save me-1"></i>
        {{ $hasWarn ? 'Simpan (Ada Peringatan)' : 'Simpan Semua Data' }}
      </button>
    @else
      <div class="alert alert-danger mb-0 py-2 px-3 rounded-pill">
        Perbaiki <strong>hard errors</strong> (mis. email kosong/tidak valid/duplikat) sebelum menyimpan.
      </div>
    @endif
  </div>

  {{-- Hidden form to keep original action target --}}
  <form id="preview-save-form" method="POST" action="{{ route('admin.employee.import.store') }}">
    @csrf
  </form>
</div>
@endsection

@push('scripts')
  {{-- SweetAlert2 --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js" defer></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Flash alerts
      @if (session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil', text: @json(session('success')), confirmButtonText: 'OK' });
      @endif
      @if (session('error'))
        Swal.fire({ icon: 'error', title: 'Gagal', text: @json(session('error')), confirmButtonText: 'OK' });
      @endif

      const hasHard = @json($hasHard);
      const hasWarn = @json($hasWarn);
      const HARD = @json($hardErrors ?? []);
      const WARN = @json($softWarnings ?? []);
      const form = document.getElementById('preview-save-form');
      const toggleAdv = document.getElementById('toggle-advanced');
      const quickFilter = document.getElementById('quick-filter');
      const tableHost = document.getElementById('table-host');

      // Toggle advanced columns
      toggleAdv?.addEventListener('change', (e) => {
        if (e.target.checked) tableHost.classList.add('show-advanced');
        else tableHost.classList.remove('show-advanced');
      });

      // Jump to first problematic row
      const btnJump = document.getElementById('btn-jump-first');
      if (btnJump) {
        btnJump.addEventListener('click', () => {
          const firstHardKey = Object.keys(HARD)[0];
          const firstWarnKey = Object.keys(WARN)[0];
          const targetKey = firstHardKey ?? firstWarnKey;
          if (!targetKey) return;
          const badge = document.querySelector(`.issue-badge[data-row="${targetKey}"]`);
          if (badge) {
            badge.closest('tr')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            badge.classList.add('blink');
            setTimeout(() => badge.classList.remove('blink'), 1200);
          }
        });
      }

      // Click badge → show details
      document.querySelectorAll('.issue-badge').forEach(b => {
        b.addEventListener('click', () => {
          const key = b.getAttribute('data-row');
          const hard = HARD[key] || [];
          const warn = WARN[key] || [];
          const html = `
            ${hard.length ? `<h6>Hard Errors</h6><ul>${hard.map(m => `<li>${m}</li>`).join('')}</ul>` : ''}
            ${warn.length ? `<h6>Warnings</h6><ul>${warn.map(m => `<li>${m}</li>`).join('')}</ul>` : ''}
          ` || 'Tidak ada detail.';
          Swal.fire({ title: `Detail Baris ${key}`, html, width: 700, confirmButtonText: 'Tutup' });
        });
      });

      // Save confirm when warnings exist
      if (form && !hasHard) {
        form.addEventListener('submit', function (e) {
          if (hasWarn) {
            e.preventDefault();
            Swal.fire({
              icon: 'warning',
              title: 'Lanjut simpan?',
              html: 'Ada <b>peringatan</b> pada beberapa baris. Data tetap akan disimpan,<br>tapi mohon lengkapi setelah import.',
              showCancelButton: true,
              confirmButtonText: 'Ya, simpan',
              cancelButtonText: 'Batal',
            }).then((res) => { if (res.isConfirmed) form.submit(); });
          }
        });
      }

      // Client-side quick filter
      const table = document.getElementById('preview-table');
      const getText = (td) => td?.textContent?.toLowerCase() ?? '';
      quickFilter?.addEventListener('input', () => {
        const q = quickFilter.value.trim().toLowerCase();
        Array.from(table.tBodies[0].rows).forEach(tr => {
          if (tr.cells.length === 0) return;
          const name = getText(tr.cells[1]);
          const email = getText(tr.cells[2]);
          const dept  = getText(tr.cells[8]);
          const pos   = getText(tr.cells[10]);
          const hit = !q || [name, email, dept, pos].some(t => t.includes(q));
          tr.style.display = hit ? '' : 'none';
        });
      });
    });
  </script>
@endpush
