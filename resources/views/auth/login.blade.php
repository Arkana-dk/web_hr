<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Login - HR Workspace</title>
  <meta name="csrf-token" content="{{ csrf_token() }}"/>

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <style>
    :root{
      /* === palette selaras dashboard === */
      --brand-500:#3B82F6;   /* blue-500 (primary) */
      --brand-600:#2563EB;   /* blue-600 (hover/strong) */
      --brand-100:#EAF2FF;   /* very light blue accent (pill/bg lembut) */
      --ink:#0F172A;         /* slate-900 */
      --muted:#6B7280;       /* slate-500/600 */
      --bg:#F8FAFC;          /* halaman (sama tone dengan dashboard) */
    }
    body{ font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial; background:var(--bg); color:var(--ink); }

    /* card feel sama seperti komponen dashboard */
    .elev{ box-shadow: 0 10px 30px rgba(2,6,23,.06); }

    /* ==== WIND LINES (biru lembut) ==== */
    .wind{ position:absolute; height:2px; background:rgba(59,130,246,.35); border-radius:2px; animation:wind-move 1.6s linear infinite; }
    .w1{ width:46px; top:18%; right:-56px; animation-delay:0s; }
    .w2{ width:70px; top:48%; right:-76px; animation-delay:.25s; }
    .w3{ width:34px; top:68%; right:-42px; animation-delay:.55s; }
    .w4{ width:60px; top:32%; right:-70px; animation-delay:.9s; opacity:.8; }
    @keyframes wind-move{ 0%{transform:translateX(0);opacity:0;} 15%{opacity:.7;} 100%{transform:translateX(-210px);opacity:0;} }

    /* dekor titik/garis tone biru-abu */
    .dot{ width:6px; height:6px; border-radius:9999px; background:rgba(2,132,199,.25); }
  </style>
</head>
<body class="min-h-screen">

  <!-- top bar (opsional) -->
  <header class="max-w-6xl mx-auto px-6 pt-6 hidden md:block">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">HR Workspace System</h1>
        <p class="text-sm text-gray-500">hris@hakunamatata.com</p>
      </div>
      <div class="space-x-3">
        
       <a href="{{ route('about-us') }}" 
   class="inline-flex items-center px-4 py-2 rounded-md text-white font-medium" 
   style="background:var(--brand-500)">
   About US
</a>

      </div>
    </div>
  </header>

  <!-- main -->
  <main class="relative">
    <div class="max-w-6xl mx-auto px-6 py-10 md:py-16">
      <div class="grid md:grid-cols-3 gap-10 items-center">

        <!-- kiri: dekor minimal -->
        <div class="hidden md:block">
          <div class="h-64 w-full relative">
            <div class="absolute left-4 bottom-2 w-16 h-28 bg-[color:var(--brand-100)] rounded-sm elev"></div>
            <div class="absolute left-28 bottom-8 w-20 h-28 bg-white border border-gray-200 rounded-md"></div>
            <div class="absolute left-2 top-4 w-40 h-28 bg-white border border-gray-200 rounded-md"></div>
            <div class="absolute left-1/2 -translate-x-1/2 top-20 w-8 h-8 dot"></div>
            <div class="absolute left-1/3 top-8 h-px w-16 bg-gray-300"></div>
          </div>
        </div>

        <!-- tengah: CARD LOGIN -->
        <div class="bg-white rounded-3xl elev px-8 md:px-10 py-10">
          <h2 class="text-xl font-bold text-center text-gray-900">Admin Login</h2>
          <p class="text-gray-500 text-sm text-center mt-1 mb-7">Hey, enter your details to get sign in to your account</p>

          <form method="POST" action="{{ route('login.attempt') }}" class="space-y-5" onsubmit="lockBtn()">
            @csrf

            <!-- email / phone -->
            <div class="relative">
              <input type="text" name="email" id="email" value="{{ old('email') }}" required
                     placeholder="Enter Email / Phone No"
                     class="w-full px-4 py-3 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[color:var(--brand-500)] outline-none"/>
              <i class="fa-regular fa-envelope absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
              @error('email')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- password -->
            <div class="relative">
              <input type="password" name="password" id="password" required
                     placeholder="Passcode"
                     class="w-full px-4 py-3 pr-14 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[color:var(--brand-500)] outline-none"/>
              <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500" onclick="togglePwd()" aria-label="Show password">
                <i id="eye" class="fa-regular fa-eye-slash"></i>
              </button>
              @error('password')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="text-xs text-gray-500">Having trouble in sign in?</div>

            <button id="submitBtn" type="submit"
                    class="w-full text-white font-semibold py-3 rounded-lg transition"
                    style="background:var(--brand-500)">Sign in</button>
          </form>

          <p class="text-center text-sm text-gray-500 mt-6">
            Trouble with login?
            <a href="#" class="font-medium hover:underline" style="color:var(--brand-600)">Forgot password</a>
          </p>
        </div>

        <!-- kanan: ilustrasi + efek angin (biru) -->
        <div class="hidden md:flex justify-center">
          <div class="relative">
            <img src="{{ asset('images/man-running.png') }}" alt="Running Man"
                 class="max-h-96 drop-shadow"/>
            <span class="wind w1"></span>
            <span class="wind w2"></span>
            <span class="wind w3"></span>
            <span class="wind w4"></span>
          </div>
        </div>
      </div>
    </div>

    <!-- footer -->
    <div class="text-center text-xs text-gray-400 pb-6">
      © {{ date('Y') }} PT. Hakuna Matata — Privacy Policy
    </div>
  </main>

  <script>
    function togglePwd(){
      const i = document.getElementById('eye');
      const p = document.getElementById('password');
      if(p.type === 'password'){ p.type='text'; i.classList.replace('fa-eye-slash','fa-eye'); }
      else { p.type='password'; i.classList.replace('fa-eye','fa-eye-slash'); }
    }
    function lockBtn(){
      const b = document.getElementById('submitBtn');
      b.disabled = true; b.style.opacity = .6; b.style.cursor='not-allowed'; b.textContent='Signing in...';
      // optional: ubah warna on disabled
      b.style.background = 'var(--brand-600)';
    }
  </script>
</body>
</html>
