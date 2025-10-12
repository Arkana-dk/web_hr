<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Lupa Password – PT. Hakuna Matata</title>
  <meta name="csrf-token" content="{{ csrf_token() }}" />

  <!-- Tailwind & SweetAlert2 CDN -->
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

  <!-- Background (opsional) -->
  <div class="fixed inset-0">
    <img src="https://rupacita.com/wp-content/uploads/2020/10/washingtonian-offices.jpg" alt="Background" class="w-full h-full object-cover filter blur-sm brightness-75" />
    <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-black opacity-30"></div>
  </div>

  <!-- Card Container -->
  <div class="relative bg-white shadow-xl rounded-3xl overflow-hidden max-w-4xl w-full flex flex-col md:flex-row z-10">

    <!-- Side Branding -->
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
      <h2 class="text-[#2f7a95] text-3xl font-bold mb-4 text-center">Lupa Password?</h2>
      <p class="text-gray-600 mb-6 text-center">Masukkan email Anda untuk menerima link reset password.</p>

      @if(session('status'))
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
          {{ session('status') }}
        </div>
      @endif

      <form method="POST" action="{{ route('password.email') }}" class="space-y-6" onsubmit="handleSubmit(event)">
        @csrf
        <div>
          <label for="email" class="block text-gray-700 mb-2">Alamat Email</label>
          <div class="relative flex items-center border border-gray-300 rounded-lg bg-white focus-within:ring-2 focus-within:ring-[#2f7a95]">
            <span class="px-3 text-gray-400"><i class="fas fa-envelope"></i></span>
            <input id="email" type="email" name="email" required autofocus
                   placeholder="you@example.com"
                   value="{{ old('email') }}"
                   class="w-full py-2 px-2 pr-10 rounded-r-lg focus:outline-none text-gray-700" />
          </div>
          @error('email')<p class="text-red-500 text-sm mt-1 ml-1">{{ $message }}</p>@enderror
        </div>

        <button id="submitBtn" type="submit"
                class="w-full bg-[#2f7a95] text-white font-bold py-2 rounded-lg hover:bg-[#256377] transition">
          Kirim Link Reset
        </button>
      </form>

      <p class="mt-4 text-center text-sm">
        <a href="{{ route('login') }}" class="text-[#2f7a95] hover:underline">← Kembali ke Login</a>
      </p>
    </div>
  </div>

  <!-- Scripts -->
  <script>
    function handleSubmit(e) {
      const btn = document.getElementById('submitBtn');
      btn.disabled = true;
      btn.classList.add('opacity-50','cursor-not-allowed');
      btn.innerText = 'Mengirim...';
    }
    document.addEventListener('DOMContentLoaded', function() {
      // Sukses
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
      // Error
      @error('email')
        Swal.fire({
          icon: 'error',
          title: 'Oops...',
          text: "{{ $message }}",
          confirmButtonText: 'Coba Lagi',
          customClass: { confirmButton: 'bg-[#2f7a95] text-white px-4 py-2 rounded' },
          backdrop: 'rgba(0,0,0,0.6)'
        });
      @enderror
    });
  </script>
</body>
</html>