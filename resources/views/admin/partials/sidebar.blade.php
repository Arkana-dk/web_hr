{{-- resources/views/admin/partials/sidebar.blade.php --}}
@php
  $is   = fn(string $name) => request()->routeIs($name);

  /* ===== OPEN STATES ===== */
  $openPegawai = $is('admin.employees.*') || $is('admin.departments.*') || $is('admin.groups.*') || $is('admin.sections.*');

  $openPresensi =
      $is('admin.attendance.*')
   || $is('admin.attendance-requests.*')
   || $is('admin.overtime-requests.*')
   || $is('admin.leave-requests.*')
   || $is('admin.leave-types.*')
   || $is('admin.leave-policies.*')
   || $is('admin.leave-entitlements.*')
   || $is('admin.leave-entitlements.generate.*')
   || $is('admin.leave-ledger.*')
   || $is('admin.leave-reports.*')
   || $is('admin.shift-change.*')
   || $is('admin.attendance-summary.*');

  $openSchedule = $is('admin.work-schedules.*') || $is('admin.shifts.*');

  $openPayroll  =
      $is('admin.payruns.*')
   || $is('admin.pay-groups.*')
   || $is('admin.pay-groups.components.*')
   || $is('admin.pay-components.*')
   || $is('admin.pay-components.rates.*')
   || $is('rates.*');
@endphp

<ul class="navbar-nav sidebar sidebar-modern accordion" id="accordionSidebar" aria-label="Admin sidebar">

  {{-- BRAND --}}
  <a class="sidebar-brand d-flex align-items-center px-3" href="{{ route('admin.dashboard') }}">
    <div class="sidebar-brand-icon"><i class="fas fa-user-shield"></i></div>
    <div class="sidebar-brand-text mx-3">Admin Panel</div>
  </a>

  <hr class="sidebar-divider my-2">

  {{-- OVERVIEW --}}
  <div class="sidebar-heading">Overview</div>
  <li class="nav-item {{ $is('admin.dashboard') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('admin.dashboard') }}">
      <i class="fas fa-fw fa-tachometer-alt"></i>
      <span>Dashboard</span>
    </a>
  </li>

  <hr class="sidebar-divider">

  {{-- ===== MANAJEMEN KARYAWAN ===== --}}
  @canany(['hr.employee.view_basic','hr.employee.view_sensitive','org.manage'])
    <div class="sidebar-heading">Manajemen Karyawan</div>

    <li class="nav-item {{ $openPegawai ? 'active' : '' }}">
      <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#navPegawai"
         aria-expanded="{{ $openPegawai ? 'true' : 'false' }}" aria-controls="navPegawai">
        <i class="fas fa-fw fa-users"></i>
        <span>Pegawai</span>
        <span class="chev"><i class="fas fa-chevron-right fa-sm"></i></span>
      </a>

      <div id="navPegawai" class="collapse {{ $openPegawai ? 'show' : '' }}" data-parent="#accordionSidebar">
        <div class="py-2 collapse-inner rounded">
          @canany(['hr.employee.view_basic','hr.employee.view_sensitive'])
            <a class="collapse-item {{ $is('admin.employees.*') ? 'active' : '' }}"
               href="{{ route('admin.employees.index') }}">Data Pegawai</a>
          @endcanany

          @can('org.manage')
            <a class="collapse-item {{ $is('admin.departments.*') ? 'active' : '' }}"
               href="{{ route('admin.departments.index') }}">Department</a>
            <a class="collapse-item {{ $is('admin.groups.*') ? 'active' : '' }}"
               href="{{ route('admin.groups.index') }}">Group</a>
            <a class="collapse-item {{ $is('admin.sections.*') ? 'active' : '' }}"
               href="{{ route('admin.sections.index') }}">Section</a>
          @endcan
        </div>
      </div>
    </li>
  @endcanany

  {{-- ===== PRESENSI ===== --}}
  @canany([
    'hr.attendance.view','attendance-request.approve','overtime-request.approve','leave-request.approve',
    'attendance-summary.view','leave.master.manage','leave.entitlement.manage','leave.ledger.view','leave.report.view',
  ])
    <li class="nav-item {{ $openPresensi ? 'active' : '' }}">
      <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#navPresensi"
         aria-expanded="{{ $openPresensi ? 'true' : 'false' }}" aria-controls="navPresensi">
        <i class="fas fa-fw fa-calendar-check"></i>
        <span>Presensi</span>
        <span class="chev"><i class="fas fa-chevron-right fa-sm"></i></span>
      </a>

      <div id="navPresensi" class="collapse {{ $openPresensi ? 'show' : '' }}" data-parent="#accordionSidebar">
        <div class="py-2 collapse-inner rounded">

          @can('hr.attendance.view')
            <a class="collapse-item {{ $is('admin.attendance.index') ? 'active' : '' }}"
               href="{{ route('admin.attendance.index') }}">Data Presensi</a>
          @endcan

          @can('attendance-request.approve')
            <a class="collapse-item {{ $is('admin.attendance-requests.*') ? 'active' : '' }}"
               href="{{ route('admin.attendance-requests.index') }}">Pengajuan Absensi</a>
          @endcan

          @can('leave-request.approve')
            <a class="collapse-item {{ $is('admin.leave-requests.*') ? 'active' : '' }}"
               href="{{ route('admin.leave-requests.index') }}">Cuti (Requests)</a>
          @endcan

          @can('overtime-request.approve')
            <a class="collapse-item {{ $is('admin.overtime-requests.*') ? 'active' : '' }}"
               href="{{ route('admin.overtime-requests.index') }}">Lembur</a>
          @endcan

          @can('shift-change.request.approve')
            <a class="collapse-item {{ $is('admin.shift-change.*') ? 'active' : '' }}"
               href="{{ route('admin.shift-change.index') }}">Pengajuan Pindah Shift</a>
          @endcan

          @can('attendance-summary.view')
            <a class="collapse-item {{ $is('admin.attendance-summary.*') ? 'active' : '' }}"
               href="{{ route('admin.attendance-summary.index') }}">Rekap Presensi</a>
          @endcan

          <div class="dropdown-divider"></div>
          <span class="collapse-header">Manajemen Cuti</span>

          @canany(['leave.master.manage','leave-request.approve'])
            <a class="collapse-item {{ $is('admin.leave-types.*') ? 'active' : '' }}"
               href="{{ route('admin.leave-types.index') }}"><i class="fas fa-tags mr-1"></i> Jenis Cuti</a>
            <a class="collapse-item {{ $is('admin.leave-policies.*') ? 'active' : '' }}"
               href="{{ route('admin.leave-policies.index') }}"><i class="fas fa-sliders-h mr-1"></i> Kebijakan Cuti</a>
          @endcanany

          @canany(['leave.entitlement.manage','leave-request.approve'])
            <a class="collapse-item {{ $is('admin.leave-entitlements.*') ? 'active' : '' }}"
               href="{{ route('admin.leave-entitlements.index') }}"><i class="fas fa-wallet mr-1"></i> Hak Cuti (Entitlements)</a>

            @can('leave.entitlement.manage')
              <a class="collapse-item {{ $is('admin.leave-entitlements.generate.*') ? 'active' : '' }}"
                 href="{{ route('admin.leave-entitlements.generate.form') }}"><i class="fas fa-bolt mr-1"></i> Generate Entitlements</a>
            @endcan
          @endcanany

          @canany(['leave.ledger.view','leave-request.approve'])
            <a class="collapse-item {{ $is('admin.leave-ledger.*') ? 'active' : '' }}"
               href="{{ route('admin.leave-ledger.index') }}"><i class="fas fa-list-alt mr-1"></i> Ledger Cuti</a>
          @endcanany

          @canany(['leave.report.view','leave-request.approve'])
            <a class="collapse-item {{ $is('admin.leave-reports.*') ? 'active' : '' }}"
               href="{{ route('admin.leave-reports.index') }}"><i class="fas fa-chart-bar mr-1"></i> Laporan Cuti</a>
          @endcanany

        </div>
      </div>
    </li>
  @endcanany

  {{-- ===== LOKASI ABSENSI ===== --}}
  @can('attendance.location.manage')
    <li class="nav-item {{ $is('admin.attendance-location-settings.*') ? 'active' : '' }}">
      <a class="nav-link" href="{{ route('admin.attendance-location-settings.index') }}">
        <i class="fas fa-map-marker-alt"></i>
        <span>Setting Lokasi Absensi</span>
      </a>
    </li>
  @endcan

  {{-- ===== JADWAL ===== --}}
  @canany(['work-schedule.manage','shift.manage'])
    <li class="nav-item {{ $openSchedule ? 'active' : '' }}">
      <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#navSchedule"
         aria-expanded="{{ $openSchedule ? 'true' : 'false' }}" aria-controls="navSchedule">
        <i class="fas fa-fw fa-calendar"></i>
        <span>Schedule</span>
        <span class="chev"><i class="fas fa-chevron-right fa-sm"></i></span>
      </a>
      <div id="navSchedule" class="collapse {{ $openSchedule ? 'show' : '' }}" data-parent="#accordionSidebar">
        <div class="py-2 collapse-inner rounded">
          @can('work-schedule.manage')
            <a class="collapse-item {{ $is('admin.work-schedules.index') ? 'active' : '' }}"
               href="{{ route('admin.work-schedules.index') }}">Work Schedule</a>
          @endcan
          @can('shift.manage')
            <a class="collapse-item {{ $is('admin.shifts.index') ? 'active' : '' }}"
               href="{{ route('admin.shifts.index') }}">Shift</a>
          @endcan
        </div>
      </div>
    </li>
  @endcanany

  {{-- ===== TRANSPORT (OT) ===== --}}
  @can('transport.setting.manage')
    <li class="nav-item {{ $is('admin.transportroutes.*') ? 'active' : '' }}">
      <a class="nav-link" href="{{ route('admin.transportroutes.index') }}">
        <i class="fas fa-fw fa-bus"></i>
        <span>Transportasi</span>
      </a>
    </li>
  @endcan

  <hr class="sidebar-divider">

  {{-- ===== MANAJEMEN KEUANGAN / PAYROLL ===== --}}
  @canany([
    'payroll.run.view','payroll.group.manage','payroll.group-component.manage',
    'payroll.component.manage','payroll.rate.manage','payroll.payslip.view_all'
  ])
    <div class="sidebar-heading">Manajemen Keuangan</div>

    @php
      $rate = request()->route('rate');
      $payComponentCtx =
          request()->route('pay_component')
          ?? request()->route('payComponent')
          ?? ($rate ? $rate->component : null);

      if (!$payComponentCtx && session()->has('last_pay_component_id')) {
          $payComponentCtx = \App\Models\PayComponent::find(session('last_pay_component_id'));
      }
    @endphp

    <li class="nav-item {{ $openPayroll ? 'active' : '' }}">
      <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#navPayroll"
         aria-expanded="{{ $openPayroll ? 'true' : 'false' }}" aria-controls="navPayroll">
        <i class="fas fa-fw fa-money-bill-wave"></i>
        <span>Payroll</span>
        <span class="chev"><i class="fas fa-chevron-right fa-sm"></i></span>
      </a>

      <div id="navPayroll" class="collapse {{ $openPayroll ? 'show' : '' }}" data-parent="#accordionSidebar">
        <div class="py-2 collapse-inner rounded">
          @can('payroll.run.view')
            <a class="collapse-item {{ $is('admin.payruns.*') ? 'active' : '' }}"
               href="{{ route('admin.payruns.index') }}">Pay Runs (Wizard)</a>
          @endcan

          @can('payroll.group.manage')
            <a class="collapse-item {{ $is('admin.pay-groups.*') ? 'active' : '' }}"
               href="{{ route('admin.pay-groups.index') }}">Pay Groups</a>
          @endcan

          @can('payroll.group-component.manage')
            <a class="collapse-item {{ $is('admin.pay-groups.components.*') ? 'active' : '' }}"
               href="{{ route('admin.pay-groups.index') }}">Group Components</a>
          @endcan

          @can('payroll.component.manage')
            <a class="collapse-item {{ ($is('admin.pay-components.*') && !$is('admin.pay-components.rates.*')) ? 'active' : '' }}"
               href="{{ route('admin.pay-components.index') }}">Pay Components</a>
          @endcan

          @can('payroll.rate.manage')
            <a class="collapse-item {{ ($is('admin.pay-components.rates.*') || $is('rates.*')) ? 'active' : '' }}"
               href="{{ $payComponentCtx ? route('admin.pay-components.rates.index', $payComponentCtx) : route('admin.pay-components.index') }}">
              Component Rates
            </a>
          @endcan
        </div>
      </div>
    </li>
  @endcanany

  {{-- ===== MANAJEMEN AKUN ===== --}}
  @canany(['admin.user.manage','admin.role.manage','admin.permission.manage'])
    <div class="sidebar-heading">Manajemen Akun</div>
    {{-- Tambahkan item modul admin di sini --}}
  @endcanany

  {{-- ===== LAIN-LAIN ===== --}}
  <div class="sidebar-heading">Lain-Lain</div>
  @can('user.role.manage')
    <li class="nav-item {{ $is('admin.user-roles.*') ? 'active' : '' }}">
      <a class="nav-link" href="{{ route('admin.user-roles.index') }}">
        <i class="fas fa-user-cog"></i>
        <span>User Roles</span>
      </a>
    </li>
  @endcan

  {{-- Logout --}}
  <li class="nav-item">
    <a class="nav-link" href="#" id="logout-link">
      <i class="fas fa-fw fa-sign-out-alt"></i>
      <span>Logout</span>
    </a>
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
  </li>

  <div class="text-center d-none d-md-inline">
    <button class="rounded-circle border-0" id="sidebarToggle" aria-label="Toggle sidebar"></button>
  </div>
</ul>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const $link = document.getElementById('logout-link');
    const $form = document.getElementById('logout-form');
    if ($link) {
      $link.addEventListener('click', (e) => {
        e.preventDefault();
        Swal.fire({
          title: 'Yakin ingin logout?',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Ya, keluar',
          cancelButtonText: 'Batal'
        }).then(res => { if (res.isConfirmed) $form.submit(); });
      });
    }
  });
</script>
@endpush
