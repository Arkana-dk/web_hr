@extends('layouts.master')

@section('title', 'About Us')

@push('styles')
<style>
  :root{
    --accent:#2563eb;
    --accent-600:#1d4ed8;
    --text-dark:#1e293b;
    --text-muted:#64748b;
  }

  /* ===== Utilities ===== */
  .container-narrow{ max-width:1100px; margin:0 auto; }

  /* ================= HERO ================= */
  .hero-wrap{ padding:2.25rem 1rem 1.25rem; }
  .hero-heading{ @apply container-narrow; } /* kalau tidak pakai Tailwind, abaikan */
  .hero-heading{ max-width:1100px; margin:0 auto 1rem; text-align:left; }
  .hero-heading .eyebrow{ font-weight:800; font-size:1.75rem; color:#0f172a; margin:0; }
  .hero-heading .eyebrow em{ color:#6b7280; font-style:normal; font-weight:700; }
  .hero-heading p{ margin:.35rem 0 0; color:#64748b; max-width:640px; font-size:.95rem; }

  .hero{
    position:relative; max-width:1100px; height:320px; margin:0 auto;
    border-radius:18px; overflow:hidden; background:#0f172a;
    box-shadow:0 18px 40px -22px rgba(2,6,23,.35);
  }
  @media (max-width:768px){ .hero{ height:220px; } }
  .hero-slider{ position:absolute; inset:0; }
 .hero-slide {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center;
  opacity: 0;
  transform: scale(0.98); /* Tambahkan efek scale saat tidak aktif */
  transition: opacity 1.2s ease, transform 1.2s ease;
}
  .hero-slide.is-active {
  opacity: 1;
  transform: scale(1); /* Kartu aktif normal */
  z-index: 1;
}
  .hero::after{ content:""; position:absolute; inset:0;
    background:linear-gradient(180deg,rgba(0,0,0,.15),rgba(0,0,0,.25)); }
  .hero-content{ position:absolute; inset:0; z-index:2; display:flex; align-items:center; justify-content:center; }
  .play-btn{
    width:64px; height:64px; border-radius:999px; border:none; cursor:pointer;
    display:grid; place-items:center; background:#fff; box-shadow:0 8px 24px rgba(0,0,0,.25);
  }
  .play-btn i{ font-size:22px; color:#111827; margin-left:3px; }
  .hero-dots{ position:absolute; z-index:3; right:14px; bottom:12px; display:flex; gap:6px; }
  .hero-dot{ width:8px; height:8px; border-radius:999px; background:rgba(255,255,255,.55); border:1px solid rgba(255,255,255,.9); cursor:pointer; padding:0; }
  .hero-dot.is-active{ background:#fff; }

  /* ================= FACES (centered) ================= */
  .faces{ padding:4rem 1rem; background:#fff; }
  .faces .inner{ max-width:1100px; margin:0 auto; }  /* <-- center keseluruhan */
  .faces-header{ text-align:center; margin-bottom:1.75rem; }
  .faces-header h2{ font-size:clamp(1.5rem,2.5vw,2rem); font-weight:800; color:var(--text-dark); margin:0 0 .25rem; }
  .faces-header p{ color:var(--text-muted); max-width:640px; margin:.25rem auto 0; }

  /* track di-center; saat overflow tetap auto scroll */
  .faces-carousel {
    display: flex;
    justify-content: center;
    flex-wrap: wrap; /* Tambahkan ini */
    gap: 1.25rem;
    padding: 1rem;
    overflow-x: visible; /* Ubah ini */
    scroll-snap-type: none; /* Nonaktifkan snap horizontal */
    }
  .faces-carousel::-webkit-scrollbar{ display:none; }

  .face-card{
    flex:0 0 auto; width:280px; max-width:88vw; scroll-snap-align:center;
    background:#f8fafc; border-radius:1rem; text-align:center;
    box-shadow:0 10px 25px -12px rgba(0,0,0,.18);
    padding:1rem 1rem 1.25rem; transition:transform .25s ease, box-shadow .25s ease;
  }
  .face-card:hover{ transform:translateY(-6px); box-shadow:0 16px 36px -14px rgba(0,0,0,.22); }

  /* gambar selalu center & proporsional di dalam frame */
  .face-card .img-wrap{
    width:100%; aspect-ratio:4/3; border-radius:.75rem; overflow:hidden; background:#e2e8f0;
  }
  .face-card img{ width:100%; height:100%; object-fit:cover; object-position:center; display:block; }

  .face-card h5{ margin:.9rem 0 .15rem; font-weight:700; color:var(--text-dark); }
  .face-card span{ font-size:.92rem; color:var(--text-muted); }



  /* ================= REVIEWS ================= */
  .reviews{ background:#f9fafb; padding:4rem 1rem; }
  .review-card{ background:#fff; border-radius:1rem; padding:1.25rem 1.35rem; box-shadow:0 10px 25px -12px rgba(0,0,0,.18); height:100%; }
  .review-card p{ color:var(--text-muted); margin:0; }
  .review-author{ margin-top:.9rem; font-weight:700; }

  @media (max-width:576px){
    .faces{ padding:3rem .75rem; }
    .faces-carousel{ gap:1rem; padding:.5rem; }
    .face-card{ width:240px; }
  }
</style>
@endpush

@section('content')

{{-- ===== HERO (heading + banner) ===== --}}
<section class="hero-wrap">
  <div class="hero-heading">
    <h2 class="eyebrow">Discover our <em>Team Project</em></h2>
    <p>Unlock smarter task management with HR Workspaces — organize, collaborate, and get things done effortlessly</p>
  </div>

  <div class="hero" id="hero">
    <div class="hero-slider" id="heroSlider" aria-live="polite">
      <div class="hero-slide is-active" style="background-image:url('{{ asset('images/about/hero1.png') }}')" role="img" aria-label="Workspace"></div>
      <div class="hero-slide" style="background-image:url('{{ asset('images/about/hero2.png') }}')" role="img" aria-label="Team"></div>
      <div class="hero-slide" style="background-image:url('{{ asset('images/about/hero3.png') }}')" role="img" aria-label="Discussion"></div>
    </div>

    <div class="hero-content">
      
    </div>

    <div class="hero-dots" id="heroDots" aria-label="Slide navigation"></div>
  </div>
</section>

{{-- ===== FACES (centered) ===== --}}
<section class="faces">
  <div class="inner">
    <div class="faces-header">
      <h2>The Faces of Innovation</h2>
      <p>Meet the people who drive our passion and bring ideas to life through creativity and teamwork.</p>
    </div>

    <div class="faces-carousel" id="facesCarousel">
      <article class="face-card">
        <div class="img-wrap">
          <img src="{{ asset('images/team/member1.png') }}" alt="Annette Black">
        </div>
        <h5>Ilham Hidayatullah</h5><span>Project Manager & Full-Stack Developer</span>
      </article>

      <article class="face-card">
        <div class="img-wrap">
          <img src="{{ asset('images/team/member2.png') }}" alt="Courtney Henry">
        </div>
        <h5>Hari Rizky Ardiantoro</h5><span>Backend Developer (API & Ionic Integration)</span>
      </article>

      <article class="face-card">
        <div class="img-wrap">
          <img src="{{ asset('images/team/member3.jpg') }}" alt="Jacob Jones">
        </div>
        <h5>Dika Permana</h5><span>UI/UX Designer</span>
      </article>

      <article class="face-card">
        <div class="img-wrap">
          <img src="{{ asset('images/team/member4.png') }}" alt="Jane Cooper">
        </div>
        <h5>Fahri Salam</h5><span>Mobile Developer (Ionic Developer)</span>
      </article>

      <article class="face-card">
        <div class="img-wrap">
          <img src="{{ asset('images/team/member5.jpg') }}" alt="Ralph Edwards">
        </div>
        <h5>Jasmin</h5><span>Technical Writer & Documentation Specialist</span>
      </article>
    </div>

    
  </div>
</section>

{{-- ===== REVIEWS ===== --}}
<section class="reviews text-center">
  <h2 class="mb-4">Loved by teams around the world</h2>
  <div class="container">
    <div class="row justify-content-center g-3">
      <div class="col-md-4">
        <div class="review-card">
          <p>“We tried several tools before, but nothing matched this one. The collaboration features are seamless and our workflow has never been smoother.”</p>
          <div class="review-author">Hr Workspaces</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="review-card">
          <p>“A true game changer! Our HR operations are faster and our employees love how intuitive everything feels.”</p>
          <div class="review-author">Hr Workspaces</div>
        </div>
      </div>
    </div>
  </div>
</section>

@endsection

@push('scripts')
<script>
/* ===== HERO: auto slide + dots + pause on hover ===== */
(function(){
  const slider = document.getElementById('heroSlider');
  const slides = Array.from(slider.querySelectorAll('.hero-slide'));
  const dotsWrap = document.getElementById('heroDots');
  const hero = document.getElementById('hero');
  let idx = 0, t=null, interval = 5000;

  slides.forEach((_, i) => {
    const b = document.createElement('button');
    b.className = 'hero-dot' + (i===0 ? ' is-active' : '');
    b.setAttribute('aria-label','Go to slide ' + (i+1));
    b.addEventListener('click', () => go(i, true));
    dotsWrap.appendChild(b);
  });
  const dots = Array.from(dotsWrap.children);

  function go(next, user=false){
    slides[idx].classList.remove('is-active'); dots[idx].classList.remove('is-active');
    idx = (typeof next==='number') ? next : (idx+1)%slides.length;
    slides[idx].classList.add('is-active'); dots[idx].classList.add('is-active');
    if(user) restart();
  }
  const start = () => t = setInterval(go, interval);
  const stop  = () => { if(t){ clearInterval(t); t=null; } };
  const restart = () => { stop(); start(); };

  hero.addEventListener('mouseenter', stop);
  hero.addEventListener('mouseleave', start);

  // swipe sederhana
  let sx=null;
  hero.addEventListener('touchstart', e=> sx=e.touches[0].clientX,{passive:true});
  hero.addEventListener('touchend', e=>{
    if(sx===null) return;
    const dx = e.changedTouches[0].clientX - sx;
    if(Math.abs(dx)>40){
      const n = dx<0 ? (idx+1)%slides.length : (idx-1+slides.length)%slides.length;
      go(n, true);
    }
    sx=null;
  });

  start();
})();

/* ===== FACES: scroll by card width (tombol di tengah) ===== */
function scrollFaces(direction){
  const el = document.getElementById('facesCarousel');
  const card = el.querySelector('.face-card');
  const gap = 20;
  const step = (card?.offsetWidth || 260) + gap;
  el.scrollBy({ left: direction * step, behavior: 'smooth' });
}
</script>
@endpush
