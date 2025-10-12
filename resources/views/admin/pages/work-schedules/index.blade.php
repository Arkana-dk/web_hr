@extends('layouts.master')

@section('title', 'Review Jadwal Kerja')

@section('content')
<div class="container-fluid py-4"><!-- ganti: container -> container-fluid -->

  {{-- Hero Card --}}
  <div class="card hero-card shadow-sm mb-4">
    <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
      <div>
        <h4 class="mb-1">📅 Review Jadwal Kerja</h4>
        <p class="mb-0">Periode:
          <strong>{{ now()->startOfMonth()->format('d M Y') }}</strong> s/d
          <strong>{{ now()->endOfMonth()->format('d M Y') }}</strong>
        </p>
      </div>
      <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
        <a href="{{ route('admin.work-schedules.import.page') }}" class="btn btn-outline-light rounded-pill">
          Import Jadwal
        </a>
        <a href="{{ route('admin.workschedule.export') }}" class="btn btn-outline-light rounded-pill">
          Download Template Jadwal
        </a>
        <a href="{{ route('admin.work-schedules.index') }}" class="btn btn-pill btn-light">Kembali</a>
      </div>
    </div>
  </div>

  

  {{-- Calendar + Side Panel --}}
  <div class="row g-4 mb-4">
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-header bg-primary text-white fw-semibold">Ringkasan Shift (Kalender)</div>
        <div class="card-body">
          <div id="calendar"></div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card h-100" id="side-panel">
        <div class="card-header bg-light fw-semibold d-flex align-items-center gap-2">
          <span class="me-1">📌</span> Panel Samping
        </div>
        <div class="card-body">
          <div id="side-panel-content" class="text-muted">
            Pilih tanggal di kalender atau klik salah satu baris pada tabel untuk melihat detail.
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Filter Bar --}}
<div class="card mb-4">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end" id="filter-form">
      {{-- Nama Karyawan (field) --}}
      <div class="col-12 col-md-4 col-lg-3">
        <label class="form-label mb-1"> Cari Data karyawan </label>
        <input type="text"
              name="name"
              id="filter-name"
              class="form-control"
              placeholder="Ketik nama atau nomor…"
              value="{{ $selected['name'] ?? '' }}">

      </div>

      {{-- Department --}}
      <div class="col-6 col-md-4 col-lg-3">
        <label class="form-label mb-1">Department</label>
        <select name="department" class="form-select" onchange="this.form.submit()">
          <option value="">— Semua Department —</option>
          @foreach($departmentOptions as $d)
            <option value="{{ $d }}" {{ ($selected['department'] ?? '') === $d ? 'selected' : '' }}>
              {{ $d }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Section --}}
      <div class="col-6 col-md-4 col-lg-3">
        <label class="form-label mb-1">Section</label>
        <select name="section" class="form-select" onchange="this.form.submit()">
          <option value="">— Semua Section —</option>
          @foreach($sectionOptions as $s)
            <option value="{{ $s }}" {{ ($selected['section'] ?? '') === $s ? 'selected' : '' }}>
              {{ $s }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Position --}}
      <div class="col-6 col-md-4 col-lg-3">
        <label class="form-label mb-1">Position</label>
        <select name="position" class="form-select" onchange="this.form.submit()">
          <option value="">— Semua Position —</option>
          @foreach($positionOptions as $p)
            <option value="{{ $p }}" {{ ($selected['position'] ?? '') === $p ? 'selected' : '' }}>
              {{ $p }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-12 d-flex gap-2 mt-2">
        <button type="submit" class="btn btn-primary btn-sm d-none">Terapkan</button>
        <a href="{{ route('admin.work-schedules.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
      </div>
    </form>
  </div>
</div>

  {{-- Tabel Detail --}}
  <div class="card">
    <div class="card-header bg-primary text-white fw-semibold">Detail Jadwal Karyawan</div>
    <div class="card-body p-0">
      <div class="table-responsive table-sticky"><!-- hapus style min-width -->
        <table class="table table-bordered table-hover mb-0 w-100 table-nowrap"><!-- tambah w-100 & table-nowrap -->
          <thead class="table-light">
            <tr>
              <th style="width:64px">#</th>
              <th>Nama</th>
              <th>Nomor Karyawan</th>
              <th>Tanggal</th>
              <th>Shift</th>
              <th>Department</th>
              <th>Section</th>
              <th>Position</th>
            </tr>
          </thead>
          <tbody>
          @foreach($schedules as $i => $item)
            <tr class="clickable-row"
                data-employee-name="{{ $item->employee_name }}"
                data-employee-number="{{ $item->employee_number }}"
                data-department="{{ $item->department }}"
                data-section="{{ $item->section }}"
                data-position="{{ $item->position }}"
                data-date="{{ \Carbon\Carbon::parse($item->work_date)->toDateString() }}"
                data-shift="{{ $item->shift_name }}">
              <td>{{ $schedules->firstItem() + $i }}</td>
              <td>{{ $item->employee_name }}</td>
              <td>{{ $item->employee_number }}</td>
              <td>{{ \Carbon\Carbon::parse($item->work_date)->format('d-m-Y') }}</td>
              <td>{{ $item->shift_name }}</td>
              <td>{{ $item->department }}</td>
              <td>{{ $item->section }}</td>
              <td>{{ $item->position }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <div class="text-muted small">
        Menampilkan {{ $schedules->firstItem() }}–{{ $schedules->lastItem() }} dari {{ $schedules->total() }} data
      </div>
      {{ $schedules->links('pagination::bootstrap-4') }}
    </div>
  </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<style>
  :root{ --soft-bg: rgba(13,110,253,.10); --soft-bd: rgba(13,110,253,.25); }
  .rounded-pill{ border-radius:999px!important; }
  .hero-card{ background: linear-gradient(135deg,#0d6efd,#6f42c1); color:#fff; border-radius:1rem; }
  .chip{ display:inline-flex; align-items:center; padding:.35rem .75rem; border-radius:999px; border:1px solid rgba(255,255,255,.35); font-weight:600; font-size:.85rem; }
  .table-sticky thead th{ background:#fff; position:sticky; top:0; z-index:1; }
  .pagination svg{ width:1rem; height:1rem; vertical-align:-.125em; }
  .pagination .hidden{ display:none!important; }
  .btn-pill { border-radius: 50rem!important; padding-left:1rem; padding-right:1rem; }
  /* HAPUS rule sempit sebelumnya */
  /* .table-responsive { min-width: 1200px; } */
  .table-responsive { width: 100%; }
  /* Biar kolom tidak pecah baris */
  .table-nowrap th, .table-nowrap td { white-space: nowrap; }

  .form-label { font-size: .85rem; color: #6c757d; }


  /* Panel */
  #side-panel .badge-chip{border-radius:999px;padding:.35rem .6rem;font-weight:600}
  #side-panel .section-title{font-size:.9rem;font-weight:700;color:#6c757d;letter-spacing:.02em;text-transform:uppercase}
  #side-panel .mini{font-size:.9rem}
  .clickable-row{cursor:pointer}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const rawEvents = @json($events);
    const calendarEl = document.getElementById('calendar');
    const sidePanel = document.getElementById('side-panel-content');

    const normalizeLabel = (shift) => {
      const s = (shift || '').toLowerCase();
      if (s.includes('libur') || s.includes('nasional')) return 'Libur';
      if (s.includes('ns')) return 'NS';
      if (s.includes('shift 1') || s === '1' || s.includes(' s1')) return 'Shift 1';
      if (s.includes('shift 2') || s === '2' || s.includes(' s2')) return 'Shift 2';
      if (s.includes('shift 3') || s === '3' || s.includes(' s3')) return 'Shift 3';
      return (shift || '-');
    };

    const indexByDate = {};
    const indexByEmployee = {};

    rawEvents.forEach(e => {
      const date = e.start;
      const [employee, rawShift] = (e.title || '').split(' - ');
      const label = normalizeLabel(rawShift);

      if (!indexByDate[date]) indexByDate[date] = [];
      indexByDate[date].push({ employee, shiftLabel: label, rawShift });

      if (employee) {
        if (!indexByEmployee[employee]) indexByEmployee[employee] = [];
        indexByEmployee[employee].push({ date, shiftLabel: label });
      }
    });

    // Auto-submit pencarian nama (debounce 500ms)
  const nameInput = document.getElementById('filter-name');
  if (nameInput) {
    let t;
    nameInput.addEventListener('input', function () {
      clearTimeout(t);
      t = setTimeout(() => nameInput.form.submit(), 500);
    });
  }

    const colorMap = {
      'NS': '#198754',
      'Shift 1': '#ffc107',
      'Shift 2': '#fd7e14',
      'Shift 3': '#0d6efd',
      'Libur': '#dc3545'
    };

    const grouped = {};
    rawEvents.forEach(e => {
      const date = e.start;
      const label = normalizeLabel((e.title.split(' - ')[1] || ''));
      if (!grouped[date]) grouped[date] = {};
      if (!grouped[date][label]) grouped[date][label] = 0;
      grouped[date][label]++;
    });

    const events = [];
    Object.entries(grouped).forEach(([date, shifts]) => {
      Object.entries(shifts).forEach(([label, count]) => {
        events.push({
          title: `${label}: ${count} org`,
          start: date,
          allDay: true,
          color: colorMap[label] || '#6c757d'
        });
      });
    });

    const fmtDate = (dStr) => {
      const d = new Date(dStr + 'T00:00:00');
      const fmt = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
      return fmt.replace('.', '');
    };

    const renderChips = (objCounts) => {
      return Object.entries(objCounts)
        .sort((a,b) => a[0].localeCompare(b[0]))
        .map(([label,count]) => `
          <span class="badge-chip me-2 mb-2" style="background:${(colorMap[label]||'#e9ecef')};color:#fff">
            ${label}: ${count}
          </span>`).join('');
    };

    function renderDayPanel(dateStr) {
      const list = indexByDate[dateStr] || [];
      const counts = {};
      list.forEach(i => counts[i.shiftLabel] = (counts[i.shiftLabel] || 0) + 1);

      const groupedByShift = {};
      list.forEach(i => {
        if (!groupedByShift[i.shiftLabel]) groupedByShift[i.shiftLabel] = [];
        groupedByShift[i.shiftLabel].push(i.employee);
      });

      let html = `
        <div class="section-title mb-1">Ringkasan Tanggal</div>
        <div class="d-flex flex-wrap mb-2">${renderChips(counts)}</div>
        <div class="mini text-muted mb-3">Tanggal: <strong>${fmtDate(dateStr)}</strong></div>
      `;

      Object.entries(groupedByShift).forEach(([label, names]) => {
        html += `
          <div class="mb-3">
            <div class="fw-semibold mb-1">${label} <span class="text-muted">(${names.length} org)</span></div>
            <div class="d-flex flex-wrap gap-2">
              ${names.sort().map(n => `<span class="badge bg-light text-dark border">${n}</span>`).join('')}
            </div>
          </div>`;
      });

      if (!list.length) {
        html += `<div class="text-muted">Belum ada jadwal pada tanggal ini.</div>`;
      }

      sidePanel.innerHTML = html;
    }

    function renderEmployeePanel(payload) {
      const { name, number, department, section, position } = payload;
      const monthEvents = (indexByEmployee[name] || []).sort((a,b) => a.date.localeCompare(b.date));

      const monthCounts = {};
      monthEvents.forEach(i => monthCounts[i.shiftLabel] = (monthCounts[i.shiftLabel] || 0) + 1);

      const todayStr = new Date().toISOString().slice(0,10);
      const next7 = monthEvents.filter(i => i.date >= todayStr).slice(0, 7);

      let html = `
        <div class="section-title mb-2">Detail Karyawan</div>
        <div class="mb-2"><strong>${name || '-'}</strong> <span class="text-muted">• ${number || '-'}</span></div>
        <div class="mini text-muted mb-3">${department || '-'} / ${section || '-'} / ${position || '-'}</div>

        <div class="section-title mb-1">KPI Bulan Ini</div>
        <div class="d-flex flex-wrap mb-3">${renderChips(monthCounts)}</div>

        <div class="section-title mb-1">7 Hari Ke Depan</div>
        ${next7.length ? `
        <ul class="list-group list-group-flush mb-3">
          ${next7.map(i => `
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span>${fmtDate(i.date)}</span>
              <span class="badge" style="background:${(colorMap[i.shiftLabel]||'#6c757d')};">${i.shiftLabel}</span>
            </li>`).join('')}
        </ul>` : `<div class="text-muted mb-3">Tidak ada jadwal dalam 7 hari ke depan.</div>`}

        <div class="section-title mb-1">Aksi Cepat</div>
        <div class="d-flex flex-wrap gap-2">
          <button type="button" class="btn btn-sm btn-outline-secondary">Swap Shift</button>
          <button type="button" class="btn btn-sm btn-outline-secondary">Tandai OFF</button>
          <button type="button" class="btn btn-sm btn-outline-secondary">Copy Minggu Lalu</button>
        </div>
      `;

      sidePanel.innerHTML = html;
    }

    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      height: 'auto',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,listMonth'
      },
      events,
      dateClick: (info) => { renderDayPanel(info.dateStr); },
      eventClick: (info) => { renderDayPanel(info.event.startStr); }
    });
    calendar.render();

    document.querySelectorAll('.clickable-row').forEach(tr => {
      tr.addEventListener('click', () => {
        const payload = {
          name: tr.dataset.employeeName,
          number: tr.dataset.employeeNumber,
          department: tr.dataset.department,
          section: tr.dataset.section,
          position: tr.dataset.position,
          date: tr.dataset.date,
          shift: tr.dataset.shift
        };
        renderEmployeePanel(payload);
        document.getElementById('side-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
  });
</script>
@endpush
