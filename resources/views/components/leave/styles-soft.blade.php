@push('styles')
<style>
  .card.soft {
    border:0;
    border-radius:1rem;
    background:linear-gradient(#fff,#fff) padding-box,
               linear-gradient(135deg, rgba(13,110,253,.18), rgba(111,66,193,.18)) border-box;
    border:1px solid transparent;
    box-shadow:0 12px 30px -20px rgba(13,110,253,.35);
  }
  .card.soft .card-header {
    background:#fff;
    border-bottom:1px solid rgba(0,0,0,.06);
    border-top-left-radius:1rem;
    border-top-right-radius:1rem;
    font-weight:600;
  }
  :root{
    --hero-grad: linear-gradient(135deg,#0d6efd,#6f42c1);
  }

  /* Hero */
  .hero-card{
    background: var(--hero-grad);
    color:#fff;
    border:0;
    border-radius:1.25rem;
    overflow:hidden;
  }
  .hero-body{ padding: 1.1rem 1.25rem; }
  @media (min-width:768px){ .hero-body{ padding: 1.35rem 1.5rem; } }
  .hero-title{ font-weight:700; letter-spacing:.2px; }
  .hero-meta{ opacity:.95; }

  .hero-actions{ display:flex; flex-wrap:wrap; gap:.5rem; }
  .btn-pill{ border-radius:999px!important; }
  .btn-elev{ box-shadow:0 10px 24px -12px rgba(13,110,253,.6); }

  /* Filter mini-hero */
  .filter-card{
    border:0; border-radius:1rem;
    background:
      linear-gradient(#ffffff,#ffffff) padding-box,
      linear-gradient(135deg, rgba(13,110,253,.25), rgba(111,66,193,.25)) border-box;
    border:1px solid transparent;
    box-shadow:0 10px 28px -20px rgba(13,110,253,.5);
  }
  .filter-card .card-body{ padding:.9rem 1rem; }
  @media (min-width:768px){ .filter-card .card-body{ padding:1rem 1.25rem; } }
  .filter-label{ font-size:.8rem; color:#6c757d; margin-bottom:.25rem }
  .pill .form-control, .pill .form-select{
    border-radius:999px; height:44px; padding:0 .9rem;
  }
  .toolbar{ display:flex; gap:.5rem; }

  /* Tabel */
  .table-sticky thead th{ position:sticky; top:0; z-index:2; background:#fff; }
  .table-nowrap th, .table-nowrap td{ white-space:nowrap; }
  .table td, .table th{ vertical-align:middle; }

  /* Avatar */
  .avatar{ width:64px; height:64px; overflow:hidden; border-radius:50%; border:2px solid #e9ecef; }
  .avatar img{ width:100%; height:100%; object-fit:cover; }

  /* Helpers */
  .d-none{ display:none!important; }
  .pagination .page-link{ padding:.25rem .5rem; font-size:.875rem; }
  /* tambah style shared lainnya di sini */
</style>
@endpush
