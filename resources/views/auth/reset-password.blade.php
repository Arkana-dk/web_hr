<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reset Password – PT. Hakuna Matata</title>
  <meta name="csrf-token" content="{{ csrf_token() }}" />

  <!-- Tailwind & SweetAlert2 -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- FontAwesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
  <!-- Google Font Poppins -->
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap");
    body { font-family: 'Poppins', sans-serif; }
  </style>
</head>
<body class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gray-100">

  <!-- Background -->
  <div class="fixed inset-0">
    <img src="https://rupacita.com/wp-content/uploads/2020/10/washingtonian-offices.jpg"
         alt="Office Background"
         class="w-full h-full object-cover filter blur-sm brightness-75" />
    <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-black opacity-30"></div>
  </div>

  <!-- Card -->
  <div class="relative bg-white shadow-xl rounded-3xl overflow-hidden max-w-4xl w-full flex flex-col md:flex-row z-10">

    <!-- Brand Side -->
    <div class="hidden md:flex md:w-1/2 bg-gradient-to-br from-[#2f7a95] to-[#4aa1b4] items-center justify-center p-8">
      <div class="text-center">
        <img src="https://storage.googleapis.com/a1aa/image/0f4ca261-12be-4f39-fccc-3d2f0cc2fdf3.jpg"
             alt="PT. Hakuna Matata Logo"
             class="w-32 h-32 mx-auto mb-4 rounded-full bg-white p-2" />
        <h3 class="text-white text-2xl font-semibold">PT. Hakuna Matata</h3>
        <p class="text-white text-sm mt-2">Empowering Your Workforce</p>
      </div>
    </div>

    <!-- Form Section -->
    <div class="w-full md:w-1/2 p-8">
      <h2 class="text-[#2f7a95] text-3xl font-bold mb-4 text-center">Reset Password</h2>
      <p class="text-gray-500 mb-6 text-center">Masukkan password baru Anda di bawah ini</p>

      @if (session('status'))
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
          {{ session('status') }}
        </div>
      @endif
      @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
          <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('password.update') }}" onsubmit="handleSubmit(event)" class="space-y-6">
        @csrf
        <input type="hidden" name="token" value="{{ old('token', $token) }}">
        <input type="hidden" name="email" value="{{ old('email', $email ?? request()->email) }}">

        <div>
          <label for="password" class="block text-gray-700 mb-2">Password Baru</label>
          <div class="relative flex items-center border border-gray-300 rounded-lg bg-white focus-within:ring-2 focus-within:ring-[#2f7a95]">
            <span class="px-3 text-gray-400"><i class="fas fa-lock"></i></span>
            <input id="password" type="password" name="password" required autofocus
                   placeholder="••••••••"
                   class="w-full py-2 px-2 pr-10 rounded-r-lg focus:outline-none text-gray-700" />
            <button type="button" onclick="togglePwd('password','eye1')" class="absolute right-3 text-gray-500 hover:text-gray-700">
              <i id="eye1" class="fas fa-eye-slash"></i>
            </button>
          </div>
        </div>

        <div>
          <label for="password_confirmation" class="block text-gray-700 mb-2">Konfirmasi Password</label>
          <div class="relative flex items-center border border-gray-300 rounded-lg bg-white focus-within:ring-2 focus-within:ring-[#2f7a95]">
            <span class="px-3 text-gray-400"><i class="fas fa-lock"></i></span>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                   placeholder="••••••••"
                   class="w-full py-2 px-2 pr-10 rounded-r-lg focus:outline-none text-gray-700" />
            <button type="button" onclick="togglePwd('password_confirmation','eye2')" class="absolute right-3 text-gray-500 hover:text-gray-700">
              <i id="eye2" class="fas fa-eye-slash"></i>
            </button>
          </div>
        </div>

        <button id="submitBtn" type="submit"
                class="w-full bg-[#2f7a95] text-white font-bold px-6 py-2 rounded-lg hover:bg-[#276a7f] transition">
          Reset Password
        </button>
      </form>

      <p class="mt-6 text-center text-gray-600 text-sm">
        <a href="{{ route('login') }}" class="text-[#2f7a95] hover:underline">Kembali ke Halaman Login</a>
      </p>
    </div>
  </div>

  <!-- Scripts -->
  <script>
    function togglePwd(inputId, iconId) {
      const input = document.getElementById(inputId);
      const icon = document.getElementById(iconId);
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye-slash','fa-eye');
      } else {
        input.type = 'password';
        icon.classList.replace('fa-eye','fa-eye-slash');
      }
    }
    function handleSubmit(e) {
      const btn = document.getElementById('submitBtn');
      btn.disabled = true;
      btn.classList.add('opacity-50','cursor-not-allowed');
      btn.innerText = 'Processing...';
    }
    document.addEventListener('DOMContentLoaded', function() {
      // Sukses: setelah berhasil reset password
      @if(session('status'))
        Swal.fire({
          icon: 'success',
          title: 'Berhasil',
          text: "{{ session('status') }}",
          confirmButtonText: 'OK',
          customClass: { confirmButton: 'bg-[#2f7a95] text-white px-4 py-2 rounded' },
          backdrop: 'rgba(0,0,0,0.6)'
        });
      @endif
      // Gagal: error validasi
      @if($errors->any())
        const msgs = {!! implode('<br>', $errors->all()) !!};
        Swal.fire({
          icon: 'error',
          title: 'Oops!',
          html: <div class='text-center text-gray-800 bg-white p-4 rounded-md'>${msgs}</div>,
          confirmButtonText: 'Close',
          customClass: { popup:'rounded-xl shadow-lg bg-white', confirmButton:'bg-[#2f7a95] text-white px-4 py-2 rounded-md' },
          backdrop: 'rgba(0,0,0,0.6)'
        });
      @endif
    });
  </script>
</body>
</html>