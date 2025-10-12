<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  {{-- CSRF untuk form dinamis --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>@yield('title', 'Dashboard')</title>

  <!-- FontAwesome -->
  <link href="{{ asset('sbadmin2/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
  <!-- Google Fonts: Nunito -->
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <!-- SB Admin 2 CSS -->
  <link href="{{ asset('sbadmin2/css/sb-admin-2.min.css') }}" rel="stylesheet">

  {{-- Select2 CSS --}}
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <style>
    .select2-container--default .select2-selection--single {
      height: 38px; padding: 6px 12px; border: 1px solid #d1d3e2; border-radius: 0.35rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 24px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
  </style>

  @stack('styles')

  {{-- Vite entry untuk JS custom (swal.js, dll) --}}
  @vite(['resources/js/app.js'])
</head>
<body id="page-top">

  <!-- Page Wrapper -->
  <div id="wrapper">

    {{-- Sidebar berdasarkan ROLE Spatie --}}
    @auth
      @hasrole('super-admin')
        @includeIf('superadmin.partials.sidebar')
      @else
        @hasanyrole('admin|system-admin|hr-admin|payroll-admin')
          @includeIf('admin.partials.sidebar')
        @else
          @hasanyrole('employee|user|payroll-staff|hr-staff')
            @includeIf('employee.partials.sidebar')
          @else
            {{-- Fallback: jika role belum diklasifikasikan, sembunyikan sidebar atau tampilkan sidebar umum --}}
            {{-- @includeIf('partials.sidebar') --}}
          @endhasanyrole
        @endhasanyrole
      @endhasrole
    @endauth

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

      <!-- Main Content -->
      <div id="content">

        {{-- Navbar berdasarkan ROLE Spatie --}}
        @auth
          @hasrole('super-admin')
            @includeIf('superadmin.partials.navbar')
          @else
            @hasanyrole('admin|system-admin|hr-admin|payroll-admin')
              @includeIf('admin.partials.navbar')
            @else
              @hasanyrole('employee|user|payroll-staff|hr-staff')
                @includeIf('employee.partials.navbar')
              @else
                {{-- Fallback navbar umum jika diperlukan --}}
                {{-- @includeIf('partials.navbar') --}}
              @endhasanyrole
            @endhasanyrole
          @endhasrole
        @endauth

        <!-- Begin Page Content -->
        <div class="container-fluid">
          @yield('content')
        </div>
        <!-- /.container-fluid -->
      </div>
      <!-- End of Main Content -->

      <!-- Footer -->
      <footer class="sticky-footer bg-white">
        <div class="container my-auto">
          <div class="text-center my-auto">
            <span>© {{ date('Y') }} Your Company</span>
          </div>
        </div>
      </footer>
      <!-- End of Footer -->
    </div>
    <!-- End of Content Wrapper -->
  </div>
  <!-- End of Page Wrapper -->

  <!-- Scroll to Top Button-->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <!-- Logout Modal-->
  <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="logoutModalLabel">Ready to Leave?</h5>
          <button class="close" type="button" data-dismiss="modal">
            <span aria-hidden="true">×</span>
          </button>
        </div>
        <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
          <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-danger">Logout</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Core JavaScript (SB Admin 2 dependencies) -->
  <script src="{{ asset('sbadmin2/vendor/jquery/jquery.min.js') }}"></script>
  <script src="{{ asset('sbadmin2/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('sbadmin2/vendor/jquery-easing/jquery.easing.min.js') }}"></script>
  <script src="{{ asset('sbadmin2/js/sb-admin-2.min.js') }}"></script>

  {{-- Flash toast --}}
  @if(session('toast_success'))
    <script>
      window.addEventListener('DOMContentLoaded', () => {
        window.alerts?.toastSuccess(@json(session('toast_success')));
      });
    </script>
  @endif
  @if(session('toast_error'))
    <script>
      window.addEventListener('DOMContentLoaded', () => {
        window.alerts?.toastError(@json(session('toast_error')));
      });
    </script>
  @endif

  @stack('scripts')
  @yield('scripts')
</body>
</html>
