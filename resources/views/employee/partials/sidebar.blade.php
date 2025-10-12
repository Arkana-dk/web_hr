{{-- resources/views/employee/partials/sidebar.blade.php --}}
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

  <!-- Sidebar - Brand -->
  <a class="sidebar-brand d-flex align-items-center justify-content-center"
     href="{{ route('employee.dashboard') }}">
    <div class="sidebar-brand-icon"><i class="fas fa-user"></i></div>
    <div class="sidebar-brand-text mx-3">Employee</div>
  </a>

  <hr class="sidebar-divider my-0">

  <!-- Dashboard -->
  <li class="nav-item {{ request()->routeIs('employee.dashboard') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('employee.dashboard') }}">
      <i class="fas fa-fw fa-tachometer-alt"></i>
      <span>Dashboard</span>
    </a>
  </li>

  <hr class="sidebar-divider">
  <div class="sidebar-heading">Transaksi</div>

  <!-- Presensi (form & riwayat di satu halaman) -->
  <li class="nav-item {{ request()->routeIs('employee.attendance.create') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('employee.attendance.create') }}">
      <i class="fas fa-fw fa-calendar-check"></i>
      <span>Presensi</span>
    </a>
  </li>

 <!-- Pengajuan Presensi -->
<li class="nav-item {{ request()->routeIs('employee.attendance.requests.*') ? 'active' : '' }}">
  <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePresensiReq"
     aria-expanded="{{ request()->routeIs('employee.attendance.requests.*') ? 'true' : 'false' }}"
     aria-controls="collapsePresensiReq">
    <i class="fas fa-fw fa-paper-plane"></i>
    <span>Pengajuan Presensi</span>
  </a>
  <div id="collapsePresensiReq"
       class="collapse {{ request()->routeIs('employee.attendance.requests.*') ? 'show' : '' }}"
       data-parent="#accordionSidebar">
    <div class="bg-white py-2 collapse-inner rounded">
      <a class="collapse-item {{ request()->routeIs('employee.attendance.requests.create') ? 'active' : '' }}"
         href="{{ route('employee.attendance.requests.create') }}">
        Ajukan Presensi
      </a>
      <a class="collapse-item {{ request()->routeIs('employee.attendance.requests.history') ? 'active' : '' }}"
         href="{{ route('employee.attendance.requests.history') }}">
        Riwayat Pengajuan
      </a>
    </div>
  </div>
</li>


  <!-- Pengajuan Lembur -->
  <li class="nav-item {{ request()->routeIs('employee.overtime.requests.*') ? 'active' : '' }}">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLembur"
       aria-expanded="{{ request()->routeIs('employee.overtime.requests.*') ? 'true' : 'false' }}"
       aria-controls="collapseLembur">
      <i class="fas fa-fw fa-clock"></i>
      <span>Pengajuan Lembur</span>
    </a>
    <div id="collapseLembur"
         class="collapse {{ request()->routeIs('employee.overtime.requests.*') ? 'show' : '' }}"
         data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <a class="collapse-item {{ request()->routeIs('employee.overtime.requests.create') ? 'active' : '' }}"
           href="{{ route('employee.overtime.requests.create') }}">
          Ajukan Lembur
        </a>
        <a class="collapse-item {{ request()->routeIs('employee.overtime.requests.history') ? 'active' : '' }}"
           href="{{ route('employee.overtime.requests.history') }}">
          Riwayat Lembur
        </a>
      </div>
    </div>
  </li>

  <!-- Pengajuan Cuti -->
  <li class="nav-item {{ request()->routeIs('employee.leave-requests.*') ? 'active' : '' }}">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseCuti"
       aria-expanded="{{ request()->routeIs('employee.leave-requests.*') ? 'true' : 'false' }}"
       aria-controls="collapseCuti">
      <i class="fas fa-fw fa-umbrella-beach"></i>
      <span>Pengajuan Cuti</span>
    </a>
    <div id="collapseCuti"
         class="collapse {{ request()->routeIs('employee.leave.*') ? 'show' : '' }}"
         data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <a class="collapse-item {{ request()->routeIs('employee.leave.request') ? 'active' : '' }}"
           href="{{ route('employee.leave.request') }}">
          Ajukan Cuti
        </a>
        <a class="collapse-item {{ request()->routeIs('employee.leave.history') ? 'active' : '' }}"
           href="{{ route('employee.leave.history') }}">
          Riwayat Cuti
        </a>
      </div>
    </div>
  </li>


  <!-- Pengajuan Pindah Shift -->
  <li class="nav-item {{ request()->routeIs('employee.shift-change-requests.*') ? 'active' : '' }}">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseShiftChange"
      aria-expanded="{{ request()->routeIs('employee.shift-change-requests.*') ? 'true' : 'false' }}"
      aria-controls="collapseShiftChange">
      <i class="fas fa-fw fa-random"></i>
      <span>Pindah Shift</span>
    </a>
    <div id="collapseShiftChange"
        class="collapse {{ request()->routeIs('employee.shift-change-requests.*') ? 'show' : '' }}"
        data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <a class="collapse-item {{ request()->routeIs('employee.shift-change-requests.create') ? 'active' : '' }}"
          href="{{ route('employee.shift-change-requests.create') }}">
          Ajukan Pindah
        </a>
        <a class="collapse-item {{ request()->routeIs('employee.shift-change-requests.history') ? 'active' : '' }}"
          href="{{ route('employee.shift-change-requests.history') }}">
          Riwayat Pindah
        </a>
      </div>
    </div>
  </li>

    <!-- Slip Gaji -->
  <li class="nav-item {{ request()->routeIs('employee.payslip.*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('employee.payslip.index') }}">
      <i class="fas fa-fw fa-receipt"></i>
      <span>Slip Gaji</span>
    </a>
  </li>

  <hr class="sidebar-divider d-none d-md-block">

  <!-- Logout -->
  <li class="nav-item">
    <a class="nav-link" href="#" data-toggle="modal" data-target="#logoutModal">
      <i class="fas fa-fw fa-sign-out-alt"></i>
      <span>Logout</span>
    </a>
  </li>

  <div class="text-center d-none d-md-inline">
    <button class="rounded-circle border-0" id="sidebarToggle"></button>
  </div>

</ul>
