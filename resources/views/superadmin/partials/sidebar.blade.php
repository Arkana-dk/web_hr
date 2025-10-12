{{-- resources/views/superadmin/partials/sidebar.blade.php --}}
@php
  // Helper: build active state sekali, supaya rapi
  $isSA    = fn(string $name) => request()->routeIs($name);
  $anySAHR = $isSA('admin.employees.*')
          || $isSA('admin.departments.*')
          || $isSA('admin.groups.*')
          || $isSA('admin.sections.*')
          || $isSA('admin.attendance.*')
          || $isSA('admin.attendance-requests.*')
          || $isSA('admin.leave-requests.*')
          || $isSA('admin.overtime-requests.*')
          || $isSA('admin.shift-change.*')
          || $isSA('admin.attendance-summary.*')
          || $isSA('admin.attendance-location-settings.*')
          || $isSA('admin.work-schedules.*')
          || $isSA('admin.shifts.*')
          || $isSA('admin.transportroutes.index')
          || $isSA('admin.leave-types.*')
          || $isSA('admin.leave-policies.*')
          || $isSA('admin.leave-entitlements.*')
          || $isSA('admin.leave-entitlements.generate.*')
          || $isSA('admin.leave-ledger.*')
          || $isSA('admin.leave-reports.*');
@endphp

<ul class="navbar-nav sidebar sidebar-modern accordion" id="accordionSidebar" aria-label="Main sidebar">

  {{-- BRAND --}}
  <a class="sidebar-brand d-flex align-items-center px-3" href="{{ route('superadmin.dashboard') }}">
    <div class="sidebar-brand-icon"><i class="fas fa-fan"></i></div>
    <div class="sidebar-brand-text mx-3">HR Workspaces</div>
  </a>

  <hr class="sidebar-divider my-2">

  {{-- SECTION: Superadmin --}}
  <div class="sidebar-heading">Overview</div>

  <li class="nav-item {{ $isSA('superadmin.dashboard') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('superadmin.dashboard') }}">
      <i class="fas fa-fw fa-tachometer-alt"></i>
      <span>Dashboard</span>
    </a>
  </li>

  <hr class="sidebar-divider">

  {{-- SECTION: Management (khusus Superadmin) --}}
  <div class="sidebar-heading">Management</div>

  @can('user.role.manage')
    <li class="nav-item {{ $isSA('admin.user-roles.*') ? 'active' : '' }}">
      <a class="nav-link" href="{{ route('admin.user-roles.index') }}">
        <i class="fas fa-user-cog"></i>
        <span>User Roles</span>
      </a>
    </li>
  @endcan

  <hr class="sidebar-divider">

  {{-- SECTION: Admin Area --}}
  <div class="sidebar-heading">Admin Area</div>

  {{-- Admin Dashboard --}}
  <li class="nav-item {{ $isSA('admin.dashboard') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('admin.dashboard') }}">
      <i class="fas fa-fw fa-home"></i>
      <span>Admin Dashboard</span>
    </a>
  </li>

  {{-- Payroll --}}
  @php 
  $openPayroll = 
      $isSA('admin.payruns.*') 
      || $isSA('admin.pay-groups.*') 
      || $isSA('admin.pay-components.*')
       || $isSA('admin.payruns-audit.*');   // <— tambahkan ini
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
        <a class="collapse-item {{ $isSA('admin.payruns.*') ? 'active' : '' }}" href="{{ route('admin.payruns.index') }}">Pay Runs (Wizard)</a>
        <a class="collapse-item {{ $isSA('admin.pay-groups.*') ? 'active' : '' }}" href="{{ route('admin.pay-groups.index') }}">Pay Groups</a>
        <a class="collapse-item {{ $isSA('admin.pay-groups.*') ? 'active' : '' }}" href="{{ route('admin.pay-groups.index') }}">Group Components</a>
        <a class="collapse-item {{ $isSA('admin.pay-components.*') ? 'active' : '' }}" href="{{ route('admin.pay-components.index') }}">Pay Components</a>
        <a class="collapse-item {{ $isSA('admin.pay-components.*') ? 'active' : '' }}" href="{{ route('admin.pay-components.index') }}">Component Rates</a>
       <a class="collapse-item {{ $isSA('admin.payruns-audit.*') ? 'active' : '' }}" href="{{ route('admin.payruns-audit.index') }}">Audit</a>
      </div>
    </div>
  </li>

  {{-- HR (group besar) --}}
  <li class="nav-item {{ $anySAHR ? 'active' : '' }}">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#navHR"
       aria-expanded="{{ $anySAHR ? 'true' : 'false' }}" aria-controls="navHR">
      <i class="fas fa-fw fa-briefcase"></i>
      <span>HR</span>
      <span class="chev"><i class="fas fa-chevron-right fa-sm"></i></span>
    </a>

    <div id="navHR" class="collapse {{ $anySAHR ? 'show' : '' }}" data-parent="#accordionSidebar">
      <div class="py-2 collapse-inner rounded">

        {{-- Subgroup: Master Data --}}
        <span class="collapse-header">Master Karyawan</span>
        <a class="collapse-item {{ $isSA('admin.employees.*') ? 'active' : '' }}"   href="{{ route('admin.employees.index') }}">Data Pegawai</a>
        <a class="collapse-item {{ $isSA('admin.departments.*') ? 'active' : '' }}" href="{{ route('admin.departments.index') }}">Department</a>
        <a class="collapse-item {{ $isSA('admin.groups.*') ? 'active' : '' }}"      href="{{ route('admin.groups.index') }}">Group</a>
        <a class="collapse-item {{ $isSA('admin.sections.*') ? 'active' : '' }}"    href="{{ route('admin.sections.index') }}">Section</a>

        <div class="dropdown-divider"></div>

        {{-- Subgroup: Presensi & Pengajuan --}}
        <span class="collapse-header">Presensi & Pengajuan</span>
        <a class="collapse-item {{ $isSA('admin.attendance.index') ? 'active' : '' }}"            href="{{ route('admin.attendance.index') }}">Data Presensi</a>
        <a class="collapse-item {{ $isSA('admin.attendance-requests.*') ? 'active' : '' }}"       href="{{ route('admin.attendance-requests.index') }}">Pengajuan Absensi</a>
        <a class="collapse-item {{ $isSA('admin.leave-requests.*') ? 'active' : '' }}"            href="{{ route('admin.leave-requests.index') }}">Cuti (Requests)</a>
        <a class="collapse-item {{ $isSA('admin.overtime-requests.*') ? 'active' : '' }}"         href="{{ route('admin.overtime-requests.index') }}">Lembur</a>
        <a class="collapse-item {{ $isSA('admin.shift-change.*') ? 'active' : '' }}"              href="{{ route('admin.shift-change.index') }}">Pindah Shift</a>
        <a class="collapse-item {{ $isSA('admin.attendance-summary.*') ? 'active' : '' }}"        href="{{ route('admin.attendance-summary.index') }}">Rekap Presensi</a>

        <div class="dropdown-divider"></div>

        {{-- Subgroup: Operasional --}}
        <span class="collapse-header">Setting Operasional</span>
        <a class="collapse-item {{ $isSA('admin.attendance-location-settings.*') ? 'active' : '' }}" href="{{ route('admin.attendance-location-settings.index') }}">Setting Lokasi Absensi</a>
        <a class="collapse-item {{ $isSA('admin.work-schedules.*') ? 'active' : '' }}"               href="{{ route('admin.work-schedules.index') }}">Work Schedules</a>
        <a class="collapse-item {{ $isSA('admin.shifts.*') ? 'active' : '' }}"                        href="{{ route('admin.shifts.index') }}">Shift</a>
        <a class="collapse-item {{ $isSA('admin.transportroutes.index') ? 'active' : '' }}"           href="{{ route('admin.transportroutes.index') }}">Transportasi (OT)</a>

        <div class="dropdown-divider"></div>

        {{-- Subgroup: Manajemen Cuti --}}
        <span class="collapse-header">Manajemen Cuti</span>
        <a class="collapse-item {{ $isSA('admin.leave-types.*') ? 'active' : '' }}"            href="{{ route('admin.leave-types.index') }}"><i class="fas fa-tags mr-1"></i> Jenis Cuti</a>
        <a class="collapse-item {{ $isSA('admin.leave-policies.*') ? 'active' : '' }}"         href="{{ route('admin.leave-policies.index') }}"><i class="fas fa-sliders-h mr-1"></i> Kebijakan Cuti</a>
        <a class="collapse-item {{ $isSA('admin.leave-entitlements.*') ? 'active' : '' }}"     href="{{ route('admin.leave-entitlements.index') }}"><i class="fas fa-wallet mr-1"></i> Hak Cuti (Entitlements)</a>
        <a class="collapse-item {{ $isSA('admin.leave-entitlements.generate.*') ? 'active' : '' }}" href="{{ route('admin.leave-entitlements.generate.form') }}"><i class="fas fa-bolt mr-1"></i> Generate Entitlements</a>
        <a class="collapse-item {{ $isSA('admin.leave-ledger.*') ? 'active' : '' }}"           href="{{ route('admin.leave-ledger.index') }}"><i class="fas fa-list-alt mr-1"></i> Ledger Cuti</a>
        <a class="collapse-item {{ $isSA('admin.leave-reports.*') ? 'active' : '' }}"          href="{{ route('admin.leave-reports.index') }}"><i class="fas fa-chart-bar mr-1"></i> Laporan Cuti</a>

      </div>
    </div>
  </li>

  {{-- Notifications --}}
  <li class="nav-item {{ $isSA('admin.notifications.index') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('admin.notifications.index') }}">
      <i class="fas fa-fw fa-bell"></i>
      <span>Notifications</span>
    </a>
  </li>

  <hr class="sidebar-divider d-none d-md-block">

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
  document.addEventListener('DOMContentLoaded', function () {
    const logoutLink = document.getElementById('logout-link');
    const logoutForm = document.getElementById('logout-form');
    if (logoutLink) {
      logoutLink.addEventListener('click', function (e) {
        e.preventDefault();
        Swal.fire({
          title: 'Yakin ingin logout?',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Ya, keluar',
          cancelButtonText: 'Batal'
        }).then((res)=>{ if(res.isConfirmed) logoutForm.submit(); });
      });
    }
  });
</script>
@endpush
