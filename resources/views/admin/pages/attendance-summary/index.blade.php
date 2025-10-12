{{-- resources/views/admin/pages/attendance-summary.blade.php --}}
@extends('layouts.master')

@section('title','Ringkasan Presensi')

@push('styles')
<style>
  /* Hero gradient (selaras tema pastel/pink) */
  .hero-card {
    background: linear-gradient(135deg, #ffe7f1 0%, #f7d9ff 45%, #e5f0ff 100%);
    border: 0;
  }
  .hero-card .stat {
    border-radius: 1rem;
    background: #ffffffb8;
    backdrop-filter: blur(4px);
  }
  /* Kartu item */
  .summary-card .list-group-item {
    border: 0;
    padding: .5rem .75rem;
  }
  .summary-card .badge {
    min-width: 2.5rem;
  }
  .filter-chips .btn {
    border-radius: 999px;
  }
</style>
@endpush

@section('content')
@php
  use Carbon\Carbon;

  $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
  $periodEnd   = Carbon::create($year, $month, 1)->endOfMonth();

  // Pastikan controller mengirim $stats berikut (fallback jika tidak ada):
  $stats = $stats ?? [
    'total_employees' => $summary->total() ?: 0,
    'workdays'        => $workdays ?? 0,
    'holidays'        => $holidays ?? 0,
    'avg_attendance'  => $avgAttendance ?? 0, // dalam %, 0-100
  ];
@endphp

<div class="container-fluid">

  {{-- ================= HERO CARD ================= --}}
  <div class="card hero-card shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between">
        <div class="mb-3 mb-lg-0">
          <h2 class="mb-1">
            <i class="fas fa-calendar-check"></i> Ringkasan Presensi Pegawai
          </h2>
          <div class="text-muted">
            Periode: <strong>{{ $periodStart->translatedFormat('d M Y') }}</strong> – <strong>{{ $periodEnd->translatedFormat('d M Y') }}</strong>
          </div>
          <div class="small text-muted">
            Terakhir diperbarui: {{ now()->translatedFormat('d M Y H:i') }}
          </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
          <a href="{{ route('admin.attendance-summary.export', ['month'=>$month,'year'=>$year,'format'=>'xlsx','search'=>$search]) }}"
             class="btn btn-outline-primary btn-sm">
            <i class="fas fa-file-excel"></i> Export Excel
          </a>
          <a href="{{ route('admin.attendance-summary.export', ['month'=>$month,'year'=>$year,'format'=>'pdf','search'=>$search]) }}"
             class="btn btn-outline-danger btn-sm">
            <i class="fas fa-file-pdf"></i> Export PDF
          </a>
        </div>
      </div>

      {{-- Stats pills --}}
      <div class="row mt-3">
        <div class="col-6 col-md-3 mb-2">
          <div class="stat p-3 h-100 shadow-sm">
            <div class="text-muted small">Pegawai Aktif</div>
            <div class="h4 mb-0">{{ $stats['total_employees'] }}</div>
          </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
          <div class="stat p-3 h-100 shadow-sm">
            <div class="text-muted small">Hari Kerja</div>
            <div class="h4 mb-0">{{ $stats['workdays'] }}</div>
          </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
          <div class="stat p-3 h-100 shadow-sm">
            <div class="text-muted small">Hari Libur</div>
            <div class="h4 mb-0">{{ $stats['holidays'] }}</div>
          </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
          <div class="stat p-3 h-100 shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="text-muted small">Rata-rata Kehadiran</div>
                <div class="h4 mb-1">{{ number_format($stats['avg_attendance'],1) }}%</div>
              </div>
            </div>
            <div class="progress" style="height:6px;">
              <div class="progress-bar" role="progressbar"
                   style="width: {{ max(0,min(100,$stats['avg_attendance'])) }}%;"
                   aria-valuenow="{{ $stats['avg_attendance'] }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
          </div>
        </div>
      </div>

      {{-- Filters --}}
      <form method="GET" class="mt-3">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end">
          <div class="form-group mb-2 mb-lg-0 mr-lg-2">
            <label class="small mb-1">Cari Nama</label>
            <div class="input-group">
              <input
                type="text"
                name="search"
                class="form-control form-control-sm"
                placeholder="Cari nama pegawai…"
                value="{{ $search ?? '' }}"
              >
              <div class="input-group-append">
                <button class="btn btn-outline-secondary btn-sm" type="submit">
                  <i class="fas fa-search"></i>
                </button>
              </div>
            </div>
          </div>

          <div class="form-group mb-2 mb-lg-0 mr-lg-2">
            <label class="small mb-1">Bulan</label>
            <select name="month" class="form-control form-control-sm">
              @foreach(range(1,12) as $m)
                <option value="{{ $m }}" {{ (int)$month === (int)$m ? 'selected' : '' }}>
                  {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="form-group mb-2 mb-lg-0 mr-lg-2">
            <label class="small mb-1">Tahun</label>
            <select name="year" class="form-control form-control-sm">
              @for($y = now()->year - 5; $y <= now()->year; $y++)
                <option value="{{ $y }}" {{ (int)$year === (int)$y ? 'selected' : '' }}>
                  {{ $y }}
                </option>
              @endfor
            </select>
          </div>

          {{-- Optional: filter departemen/section jika controller sediakan $departments/$sections --}}
          @if(!empty($departments ?? []))
            <div class="form-group mb-2 mb-lg-0 mr-lg-2">
              <label class="small mb-1">Departemen</label>
              <select name="department_id" class="form-control form-control-sm">
                <option value="">Semua</option>
                @foreach($departments as $dep)
                  <option value="{{ $dep->id }}" {{ request('department_id')==$dep->id ? 'selected' : '' }}>
                    {{ $dep->name }}
                  </option>
                @endforeach
              </select>
            </div>
          @endif

          <div class="ml-lg-auto">
            <button class="btn btn-primary btn-sm" type="submit">
              <i class="fas fa-filter"></i> Tampilkan
            </button>
            <a href="{{ route('admin.attendance-summary.index') }}" class="btn btn-light btn-sm">
              Reset
            </a>
          </div>
        </div>
      </form>

      {{-- Active filter chips --}}
      <div class="filter-chips mt-2">
        @if(!empty($search))
          <span class="btn btn-outline-secondary btn-sm disabled">Nama: “{{ $search }}”</span>
        @endif
        <span class="btn btn-outline-secondary btn-sm disabled">
          Periode: {{ $periodStart->translatedFormat('M Y') }}
        </span>
        @if(request('department_id') && !empty($departments ?? []))
          @php $depName = optional($departments->firstWhere('id', request('department_id')))->name; @endphp
          <span class="btn btn-outline-secondary btn-sm disabled">
            Departemen: {{ $depName }}
          </span>
        @endif
      </div>
    </div>
  </div>

  {{-- ================= GRID CARDS ================= --}}
  <div class="row">
    @forelse($summary as $item)
      @php
        // Hitung presentase kehadiran per pegawai (aman jika workdays 0)
        $present = (int)($item['present'] ?? 0);
        $workdays = max(1, (int)($stats['workdays'] ?? 0)); // hindari /0
        $pct = round(($present / $workdays) * 100);
      @endphp
      <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
        <div class="card shadow-sm h-100 summary-card">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h5 class="card-title mb-0">
                <i class="fas fa-user-circle"></i>
                {{ $item['employee_name'] }}
              </h5>
              @isset($item['employee_id'])
                <a href="{{ route('admin.attendance.detail', ['employee'=>$item['employee_id'], 'month'=>$month, 'year'=>$year]) }}"
                   class="btn btn-light btn-sm" title="Lihat detail">
                  <i class="fas fa-external-link-alt"></i>
                </a>
              @endisset
            </div>

            <div class="small text-muted mb-2">
              {{ $item['position'] ?? '-' }} @if(!empty($item['department'])) • {{ $item['department'] }} @endif
            </div>

            <ul class="list-group list-group-flush flex-grow-1">
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="fas fa-check-circle text-success"></i> Hadir</span>
                <span class="badge badge-success">{{ $item['present'] }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="fas fa-clock text-warning"></i> Terlambat</span>
                <span class="badge badge-warning">{{ $item['late'] }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="fas fa-user-md text-info"></i> Izin/Cuti</span>
                <span class="badge badge-info">{{ $item['cuti'] }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="fas fa-times-circle text-danger"></i> Tidak Hadir</span>
                <span class="badge badge-danger">{{ $item['absent'] }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="fas fa-stopwatch text-primary"></i> Lembur (jam)</span>
                <span class="badge badge-primary">{{ $item['overtime_hours'] }}</span>
              </li>
            </ul>

            <div class="mt-3">
              <div class="d-flex justify-content-between small">
                <span class="text-muted">Kehadiran</span>
                <span><strong>{{ $pct }}%</strong></span>
              </div>
              <div class="progress" style="height:6px;">
                <div class="progress-bar" role="progressbar"
                    style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div>

            @isset($item['employee_id'])
            <div class="mt-3 d-flex">
              <a href="{{ route('admin.attendance.detail', ['employee'=>$item['employee_id'], 'month'=>$month, 'year'=>$year]) }}"
                 class="btn btn-outline-primary btn-sm mr-2">
                <i class="fas fa-eye"></i> Detail
              </a>
              @if(isset($item['export_url']))
                <a href="{{ $item['export_url'] }}" class="btn btn-light btn-sm">
                  <i class="fas fa-download"></i> Unduh
                </a>
              @endif
            </div>
            @endisset
          </div>
        </div>
      </div>
    @empty
      <div class="col-12">
        <div class="alert alert-info text-center">
          <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
          Belum ada data ringkasan presensi untuk periode ini.
        </div>
      </div>
    @endforelse
  </div>

  {{-- ================= PAGINATION ================= --}}
  <div class="d-flex justify-content-center">
    {{ $summary->withQueryString()->links('pagination::bootstrap-4') }}
  </div>
</div>
@endsection
