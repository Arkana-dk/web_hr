{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.master')
@section('title', 'Admin Dashboard')

@push('styles')
<style>
  /* ===== Hero Card ===== */
  .hero-card{
    position:relative; overflow:hidden;
    background:
      radial-gradient(1200px 400px at 10% -10%, rgba(59,130,246,.25), transparent 60%),
      radial-gradient(1200px 400px at 90% -10%, rgba(236,72,153,.25), transparent 60%),
      linear-gradient(135deg,#ffffff,#f8fafc);
    border:1px solid rgba(0,0,0,.05);
    backdrop-filter: blur(4px);
    border-radius: .75rem;
  }
  .hero-shine{ position:absolute; inset:0; pointer-events:none;
    background: radial-gradient(400px 120px at 80% 5%, rgba(255,255,255,.55), transparent 60%);
  }
  .hero-emoji{ font-size:56px; line-height:1; filter:drop-shadow(0 10px 18px rgba(0,0,0,.08)); transform:translateY(2px); }

  /* ===== KPI ===== */
  .kpi-card{ transition:transform .25s ease, box-shadow .25s ease; border-radius:.75rem; }
  .kpi-card:hover{ transform: translateY(-4px); box-shadow:0 10px 24px rgba(0,0,0,.10)!important; }
  .kpi-sub{ font-size:.8rem; color:#6b7280 }
  .progress-slim{ height:6px; border-radius:8px; overflow:hidden; background:#eef2ff; }

  /* ===== Chart Card ===== */
  .chart-card{ background:#fff; border:1px solid rgba(0,0,0,.06); border-radius:.75rem; box-shadow:0 4px 12px rgba(0,0,0,.04); }
  .chart-card__header{ padding:.9rem 1rem; border-bottom:1px solid #f1f5f9; font-weight:700; color:#0f172a }
  .chart-card__body{ padding:1rem 1rem 1.2rem; }
  .square{ aspect-ratio:1/1; }

  /* ===== List approval ===== */
  .list-klean{ list-style:none; padding:0; margin:0; }
  .list-klean li{ display:flex; gap:.75rem; align-items:flex-start; padding:.6rem 0; border-bottom:1px dashed #eef2f7; }
  .list-klean li:last-child{ border-bottom:0; }
  .avatar-xs{ width:36px; height:36px; border-radius:50%; object-fit:cover; }
  .badge-soft{ background:#eef2ff; color:#3730a3; border-radius:999px; padding:.2rem .5rem; font-size:.75rem; font-weight:600; }
</style>
@endpush

@section('content')
@php
  // Force HR only if not provided
  $showHR = $showHR ?? true;

  // Safe fallbacks
  $totalEmployees     = $totalEmployees     ?? 0;
  $newHiresThisMonth  = $newHiresThisMonth  ?? 0;
  $employeesThisMonth = $employeesThisMonth ?? 0;
  $employeePercent    = $employeePercent    ?? 0;
  $employeeTarget     = $employeeTarget     ?? 0;
  $absentToday        = $absentToday        ?? 0;
  $absentPercent      = $absentPercent      ?? 0;
  $presentToday       = $presentToday       ?? 0;
  $presentPercent     = $presentPercent     ?? 0;

  $pendingLeaveCount  = $pendingLeaveCount  ?? 0;
  $pendingOtCount     = $pendingOtCount     ?? 0;
  $pendingShiftCount  = $pendingShiftCount  ?? 0;

  $deptLabels         = $deptLabels         ?? [];
  $deptCounts         = $deptCounts         ?? [];
  $leaveLabels        = $leaveLabels        ?? [];   // e.g. Approved/Pending/Rejected
  $leaveCounts        = $leaveCounts        ?? [];
  $presenceLabels     = $presenceLabels     ?? [];   // e.g. dates
  $presenceCounts     = $presenceCounts     ?? [];   // e.g. present counts
@endphp

{{-- ===== Hero Card ===== --}}
<div class="hero-card mb-4 shadow-sm">
  <div class="hero-shine"></div>
  <div class="p-4 p-lg-5">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center">
      <div class="flex-grow-1">
        <div class="d-flex align-items-center">
          <span class="hero-emoji mr-3">👋</span>
          <div>
            <h1 class="h4 mb-1 text-gray-800">Selamat datang di Dashboard Admin (HR)</h1>
            <p class="mb-0 text-muted">Pantau headcount, kehadiran, dan pengajuan cuti—semua dalam satu tempat.</p>
          </div>
        </div>

        <div class="d-flex flex-wrap align-items-center mt-3" style="gap:.5rem 1rem">
          <span class="badge-soft"><i class="fas fa-users mr-1"></i> Total karyawan: {{ number_format($totalEmployees) }}</span>
          <span class="badge-soft"><i class="fas fa-user-check mr-1"></i> Hadir hari ini: {{ number_format($presentToday) }}</span>
          <span class="badge-soft"><i class="fas fa-inbox mr-1"></i> Approval pending: {{ $pendingLeaveCount + $pendingOtCount + $pendingShiftCount }}</span>
        </div>
      </div>

      <div class="mt-3 mt-lg-0 ml-lg-4">
        
      </div>
    </div>
  </div>
</div>

@if($showHR)
  {{-- ===== KPI ROW ===== --}}
  <div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card kpi-card border-left-primary shadow h-100 py-2">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Karyawan</div>
              <div class="h3 mb-0 font-weight-bold text-gray-800">{{ number_format($totalEmployees) }}</div>
              <div class="kpi-sub">+{{ number_format($newHiresThisMonth) }} hire(s) bulan ini</div>
            </div>
            <i class="fas fa-users fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card kpi-card border-left-success shadow h-100 py-2">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div style="min-width:0">
              <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Rekrutmen Bulan Ini</div>
              <div class="h3 mb-0 font-weight-bold text-gray-800">{{ number_format($employeesThisMonth) }}</div>
              <div class="progress progress-slim mt-2 mb-1">
                <div class="progress-bar bg-success" style="width:{{ max(0,min(100,$employeePercent)) }}%"></div>
              </div>
              <div class="kpi-sub">{{ number_format($employeePercent) }}% dari target ({{ number_format($employeeTarget) }})</div>
            </div>
            <i class="fas fa-briefcase fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card kpi-card border-left-danger shadow h-100 py-2">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Absen Hari Ini</div>
              <div class="h3 mb-0 font-weight-bold text-gray-800">{{ number_format($absentToday) }}</div>
              <div class="progress progress-slim mt-2 mb-1">
                <div class="progress-bar bg-danger" style="width:{{ max(0,min(100,$absentPercent)) }}%"></div>
              </div>
              <div class="kpi-sub">{{ number_format($absentPercent) }}% karyawan absen</div>
            </div>
            <i class="fas fa-user-times fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card kpi-card border-left-info shadow h-100 py-2">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Presensi Hari Ini</div>
              <div class="h3 mb-0 font-weight-bold text-gray-800">
                {{ number_format($presentToday) }} <small class="text-muted">/ {{ number_format($totalEmployees) }}</small>
              </div>
              <div class="progress progress-slim mt-2 mb-1">
                <div class="progress-bar bg-info" style="width:{{ max(0,min(100,$presentPercent)) }}%"></div>
              </div>
              <div class="kpi-sub">{{ number_format($presentPercent) }}% hadir</div>
            </div>
            <i class="fas fa-user-check fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ===== Charts + Approval Panel ===== --}}
  <div class="row">
    <div class="col-xl-8 col-lg-7 mb-4">
      <div class="chart-card mb-4">
        <div class="chart-card__header">Distribusi Karyawan per Departemen</div>
        <div class="chart-card__body"><canvas id="deptBar"></canvas></div>
      </div>

      <div class="chart-card">
        <div class="chart-card__header">Tren Kehadiran (7–14 hari terakhir)</div>
        <div class="chart-card__body"><canvas id="presenceLine"></canvas></div>
      </div>
    </div>

    <div class="col-xl-4 col-lg-5 mb-4">
      <div class="chart-card h-100">
        <div class="chart-card__header">Status Permintaan Cuti (Bulan Ini)</div>
        <div class="chart-card__body">
          <canvas id="leaveStatusPie" class="square mb-3"></canvas>

          <hr>
          <div class="d-flex align-items-center justify-content-between mb-2">
            <strong>Permintaan Pending</strong>
            <span class="badge-soft"><i class="fas fa-inbox mr-1"></i>{{ $pendingLeaveCount + $pendingOtCount + $pendingShiftCount }}</span>
          </div>
          <ul class="list-klean">
            <li>
              <img class="avatar-xs" src="{{ asset('images/avatar-default.png') }}" alt="">
              <div>
                <div class="font-weight-bold">Cuti</div>
                <div class="text-muted small">Menunggu persetujuan</div>
              </div>
              <span class="ml-auto badge-soft">{{ $pendingLeaveCount }}</span>
            </li>
            <li>
              <img class="avatar-xs" src="{{ asset('images/avatar-default.png') }}" alt="">
              <div>
                <div class="font-weight-bold">Lembur</div>
                <div class="text-muted small">Menunggu persetujuan</div>
              </div>
              <span class="ml-auto badge-soft">{{ $pendingOtCount }}</span>
            </li>
            <li>
              <img class="avatar-xs" src="{{ asset('images/avatar-default.png') }}" alt="">
              <div>
                <div class="font-weight-bold">Perubahan Shift</div>
                <div class="text-muted small">Menunggu persetujuan</div>
              </div>
              <span class="ml-auto badge-soft">{{ $pendingShiftCount }}</span>
            </li>
          </ul>

          <div class="mt-3">
            <a href="{{ route('admin.leave-requests.index',['status'=>'pending']) }}" class="btn btn-primary btn-block">
              Tinjau Semua Approval
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
@endif

{{-- Fallback kalau HR = false --}}
@if(!$showHR)
  <div class="alert alert-info">
    Selamat datang! Modul HR tidak diaktifkan untuk akun Anda.
  </div>
@endif
@endsection

@push('scripts')
{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
  const soft = ['#4e79a7','#f28e2b','#e15759','#76b7b2','#59a14f','#edc949','#af7aa1','#ff9da7','#9c755f','#bab0ab'];

  // Data dari server (aman jika undefined)
  const deptLabels     = @json($deptLabels);
  const deptCounts     = @json($deptCounts);
  const leaveLabels    = @json($leaveLabels);
  const leaveCounts    = @json($leaveCounts);
  const presenceLabels = @json($presenceLabels);
  const presenceCounts = @json($presenceCounts);

  // Bar: Employees by Department
  const elDept = document.getElementById('deptBar');
  if (elDept && deptLabels && deptLabels.length) {
    new Chart(elDept, {
      type: 'bar',
      data: {
        labels: deptLabels,
        datasets: [{
          label: 'Jumlah',
          data: deptCounts,
          borderColor: soft[0],
          backgroundColor: soft[0] + '33', // 20% opacity
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true, ticks: { precision:0 } }
        },
        plugins: { legend: { display:false } }
      }
    });
  }

  // Line: Presence Trend
  const elPresence = document.getElementById('presenceLine');
  if (elPresence && presenceLabels && presenceLabels.length) {
    new Chart(elPresence, {
      type: 'line',
      data: {
        labels: presenceLabels,
        datasets: [{
          label: 'Hadir',
          data: presenceCounts,
          borderColor: soft[2],
          backgroundColor: soft[2] + '22',
          tension: .3,
          fill: true,
          pointRadius: 3,
          borderWidth: 2
        }]
      },
      options: {
        responsive:true,
        maintainAspectRatio:false,
        scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } },
        plugins:{ legend:{ display:false } }
      }
    });
  }

  // Pie: Leave Status
  const elLeave = document.getElementById('leaveStatusPie');
  if (elLeave && leaveLabels && leaveLabels.length) {
    new Chart(elLeave, {
      type: 'doughnut',
      data: {
        labels: leaveLabels,
        datasets: [{
          data: leaveCounts,
          borderWidth: 2,
          backgroundColor: soft.slice(0, leaveLabels.length)
        }]
      },
      options: {
        responsive:true,
        maintainAspectRatio:false,
        plugins: {
          legend: { position:'bottom' },
          tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.formattedValue}` } }
        },
        cutout: '60%'
      }
    });
  }
})();
</script>
@endpush
