<!-- resources/views/admin/partials/navbar.blade.php -->
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

  <!-- Sidebar Toggle (Topbar) -->
  <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
    <i class="fa fa-bars"></i>
  </button>

  <!-- Topbar Navbar -->
  <ul class="navbar-nav ml-auto">

    {{-- 🔔 Notifikasi --}}
    @php
      use App\Models\Notification;
      $latestNotifications = Notification::latest()->take(5)->get();
      $unreadCount = Notification::where('is_read', false)->count();
    @endphp

    <li class="nav-item dropdown no-arrow mx-1">
      <a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="button"
         data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-bell fa-fw"></i>
        @if ($unreadCount > 0)
          <span class="badge badge-danger badge-counter">{{ $unreadCount }}</span>
        @endif
      </a>

      <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
           aria-labelledby="notifDropdown">
        <h6 class="dropdown-header">Notifikasi Terbaru</h6>
        @forelse ($latestNotifications as $notif)
          <a class="dropdown-item d-flex align-items-center" href="{{ route('admin.notifications.index') }}">
            <div class="font-weight-bold">
              <div class="text-truncate">{{ $notif->title }}</div>
              <div class="small text-gray-500">{{ \Illuminate\Support\Str::limit($notif->message, 40) }}</div>
              <div class="small text-gray-400">{{ $notif->created_at->diffForHumans() }}</div>
            </div>
          </a>
        @empty
          <span class="dropdown-item text-muted">Tidak ada notifikasi</span>
        @endforelse
        <a class="dropdown-item text-center small text-primary" href="{{ route('admin.notifications.index') }}">
          Lihat Semua Notifikasi
        </a>
      </div>
    </li>

    <!-- User Information -->
    <li class="nav-item dropdown no-arrow">
      <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
         data-toggle="dropdown">
        <span class="mr-2 d-none d-lg-inline text-gray-600 small">{{ Auth::user()->name }}</span>
      </a>
      <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
           aria-labelledby="userDropdown">
        <!-- Logout -->
        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
          <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>Logout
        </a>
      </div>
    </li>

  </ul>
</nav>
