<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
  <!-- Sidebar Toggle (Topbar) -->
  <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
    <i class="fa fa-bars"></i>
  </button>

  <!-- Topbar Navbar -->
  <ul class="navbar-nav ml-auto">

    {{-- Notifikasi (dropdown) --}}
    @php
      use Illuminate\Support\Str;

      $employee = Auth::user()->employee ?? null;

      $unreadCount = 0;
      $latestNotifications = collect();

      if ($employee) {
        $unreadCount = \App\Models\Notification::where('employee_id', $employee->id)
                          ->where('is_read', false)
                          ->count();

        $latestNotifications = \App\Models\Notification::where('employee_id', $employee->id)
                              ->orderByDesc('created_at')
                              ->limit(5)
                              ->get();
      }
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
           aria-labelledby="notifDropdown" style="min-width: 320px;">
        <h6 class="dropdown-header d-flex justify-content-between align-items-center">
          <span>Notifikasi Terbaru</span>
          <a href="{{ route('employee.notifications') }}" class="small">Lihat Semua</a>
        </h6>

        @forelse ($latestNotifications as $n)
          <a class="dropdown-item d-flex align-items-start {{ $n->is_read ? 'bg-light' : '' }}"
             href="{{ route('employee.notifications') }}">
            <div class="mr-3">
              @switch($n->type)
                @case('late')                           @php $icon='fa-clock';     $badge='warning'; @endphp @break
                @case('early_leave')                    @php $icon='fa-running';   $badge='info';    @endphp @break
                @case('attendance_request')             @php $icon='fa-file-alt';  $badge='secondary'; @endphp @break
                @case('attendance_request_approved')    @php $icon='fa-check';     $badge='success'; @endphp @break
                @case('attendance_request_rejected')    @php $icon='fa-times';     $badge='danger';  @endphp @break
                @case('overtime_request')               @php $icon='fa-briefcase'; $badge='primary'; @endphp @break
                @case('overtime_request_approved')      @php $icon='fa-check';     $badge='success'; @endphp @break
                @case('overtime_request_rejected')      @php $icon='fa-times';     $badge='danger';  @endphp @break
                @default                                @php $icon='fa-bell';      $badge='light';   @endphp
              @endswitch
              <span class="badge badge-{{ $badge }}"><i class="fas {{ ' '.$icon }}"></i></span>
            </div>
            <div class="flex-grow-1">
              <div class="font-weight-bold small">{{ $n->title }}</div>
              <div class="small text-gray-700">{{ Str::limit($n->message, 80) }}</div>
              <div class="small text-gray-500">{{ $n->created_at->diffForHumans() }}</div>
            </div>
          </a>
        @empty
          <div class="dropdown-item text-center small text-gray-500">
            Tidak ada notifikasi
          </div>
        @endforelse

        <div class="dropdown-divider m-0"></div>
        <div class="p-2 text-center">
          <a class="btn btn-sm btn-outline-primary" href="{{ route('employee.notifications') }}">
            Kelola Notifikasi
          </a>
        </div>
      </div>
    </li>

    <!-- User Info -->
    <li class="nav-item dropdown no-arrow">
      <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
         data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <span class="mr-2 d-none d-lg-inline text-gray-600 small">
          {{ Auth::user()->name }}
        </span>
        @if(Auth::user()->photo)
          <img class="img-profile rounded-circle"
               src="{{ asset('storage/' . Auth::user()->photo) }}"
               style="width:32px; height:32px; object-fit:cover;">
        @else
          <i class="fas fa-user-circle fa-2x text-gray-400"></i>
        @endif
      </a>
      <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
           aria-labelledby="userDropdown">

        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="#"
           onclick="event.preventDefault(); if (confirm('Anda yakin ingin logout?')) document.getElementById('logout-form').submit();">
          <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
          Logout
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
          @csrf
        </form>
      </div>
    </li>

  </ul>
</nav>
