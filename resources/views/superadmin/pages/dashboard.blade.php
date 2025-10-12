{{-- resources/views/superadmin/pages/dashboard.blade.php --}}
@extends('layouts.master')

@section('title', 'SuperAdmin Dashboard')

@push('styles')
<style>
  .card-hover{transition:transform .3s ease,box-shadow .3s ease}
  .card-hover:hover{transform:translateY(-5px) scale(1.02);box-shadow:0 8px 20px rgba(0,0,0,.15)!important}
  .kpi-delta.up   { color:#16a34a }  /* hijau */
  .kpi-delta.down { color:#dc2626 }  /* merah */
  .mini-calendar .day{width:calc(100%/7); aspect-ratio:1/1; display:flex; align-items:center; justify-content:center; border-radius:.5rem; font-weight:600}
  .mini-calendar .day.muted{opacity:.35; font-weight:500}
  .mini-calendar .day.today{background:#eef2ff}
  .list-unstyled.klean > li{display:flex; gap:.75rem; align-items:flex-start; padding:.5rem 0; border-bottom:1px dashed #eee}
  .list-unstyled.klean > li:last-child{border-bottom:0}
  .avatar-xs{width:36px;height:36px;border-radius:50%;object-fit:cover}
  .table-sm td, .table-sm th{padding:.45rem .6rem}
</style>
@endpush

@section('content')
  {{-- ===================== Header kecil (opsional) ===================== --}}
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Superadmin Dashboard</h1>

  </div>

  {{-- ===================== HERO SLIDER (3–4 gambar) ===================== --}}
  <div id="heroCarousel" class="carousel slide mb-4" data-ride="carousel" data-interval="6000" data-pause="hover">
    <ol class="carousel-indicators">
      @foreach(($heroSlides ?? []) as $i => $slide)
        <li data-target="#heroCarousel" data-slide-to="{{ $i }}" class="{{ $i===0?'active':'' }}"></li>
      @endforeach
      @if(empty($heroSlides))
        <li data-target="#heroCarousel" data-slide-to="0" class="active"></li>
        <li data-target="#heroCarousel" data-slide-to="1"></li>
        <li data-target="#heroCarousel" data-slide-to="2"></li>
      @endif
    </ol>

    <div class="carousel-inner rounded shadow-sm">
      @forelse($heroSlides ?? [] as $i => $slide)
        <div class="carousel-item {{ $i===0?'active':'' }}">
          <div class="d-flex align-items-center p-4"
               style="min-height:170px; background: {{ $slide['bg'] ?? 'linear-gradient(90deg,#6d28d9,#2563eb)' }};">
            <div class="text-white">
              <h4 class="mb-1">{{ $slide['title'] ?? 'Welcome, Superadmin!' }}</h4>
              <p class="mb-0">{{ $slide['subtitle'] ?? 'Monitor everything at a glance' }}</p>
            </div>
            @if(!empty($slide['image']))
              <img src="{{ $slide['image'] }}" alt="" class="ml-auto d-none d-md-block" style="max-height:140px;">
            @endif
          </div>
        </div>
      @empty
        @php $welcome = auth()->user()->name ?? 'Superadmin'; @endphp
        <div class="carousel-item active">
          <div class="d-flex align-items-center p-4" style="min-height:170px;background:linear-gradient(90deg,#6d28d9,#2563eb);">
            <div class="text-white">
              <h4 class="mb-1">Welcome, {{ $welcome }}!</h4>
              <p class="mb-0">You have {{ $kpi['pending_approvals']['value'] ?? 0 }} approvals waiting today.</p>
            </div>
          </div>
        </div>
        <div class="carousel-item">
          <div class="d-flex align-items-center p-4" style="min-height:170px;background:linear-gradient(90deg,#0ea5e9,#22c55e);">
            <div class="text-white">
              <h4 class="mb-1">Payroll Cutoff</h4>
              <p class="mb-0">Cutoff: {{ $payroll_cutoff ?? now()->endOfMonth()->format('d M Y') }}</p>
            </div>
          </div>
        </div>
        <div class="carousel-item">
          <div class="d-flex align-items-center p-4" style="min-height:170px;background:linear-gradient(90deg,#f59e0b,#ef4444);">
            <div class="text-white">
              <h4 class="mb-1">Compliance Reminder</h4>
              <p class="mb-0">{{ ($contracts_expiring ?? 0) }} contracts expiring this month.</p>
            </div>
          </div>
        </div>
      @endforelse
    </div>

    <a class="carousel-control-prev" href="#heroCarousel" role="button" data-slide="prev">
      <span class="carousel-control-prev-icon"></span>
    </a>
    <a class="carousel-control-next" href="#heroCarousel" role="button" data-slide="next">
      <span class="carousel-control-next-icon"></span>
    </a>
  </div>

  {{-- ===================== KPI CARDS ===================== --}}
  <div class="row">
    @foreach(($kpi ?? []) as $key => $item)
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-hover shadow h-100 py-2">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <div class="text-xs font-weight-bold text-uppercase mb-1">{{ $item['label'] }}</div>
                <div class="h4 mb-0 font-weight-bold text-gray-800">
                  {{ is_numeric($item['value']) ? number_format($item['value']) : $item['value'] }}
                </div>
                @isset($item['delta'])
                  <small class="kpi-delta {{ ($item['delta'] ?? 0) >= 0 ? 'up' : 'down' }}">
                    <i class="fas {{ ($item['delta'] ?? 0) >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                    {{ number_format(abs($item['delta']),1) }}%
                    @if(!empty($item['hint'])) <span class="text-muted">• {{ $item['hint'] }}</span> @endif
                  </small>
                @endisset
              </div>
              @if(!empty($item['icon'])) <i class="{{ $item['icon'] }} fa-2x text-gray-300"></i> @endif
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  {{-- ===================== GRID: Birthdays | Out of Office (Sick) | Payments ===================== --}}
  <div class="row">
    {{-- Birthdays --}}
    <div class="col-xl-4 mb-4">
      <div class="card shadow h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>Birthdays</strong>
          <span class="badge badge-light">This Month</span>
        </div>
        <div class="card-body">
          @if(($birthdays ?? collect())->isNotEmpty())
            <ul class="list-unstyled klean mb-0">
              @foreach($birthdays as $b)
                <li>
                  <img class="avatar-xs" src="{{ $b->avatar_url ?? asset('images/avatar-default.png') }}" alt="">
                  <div>
                    <div class="font-weight-bold">{{ $b->name }}</div>
                    <div class="text-muted small">
                      {{ $b->position?->name ?? '-' }} • {{ optional($b->date_of_birth)->format('d M') }}
                    </div>
                  </div>
                  <div class="ml-auto">
                    <a href="{{ route('admin.employees.show',$b->id) }}" class="btn btn-sm btn-outline-primary">Congrats</a>
                  </div>
                </li>
              @endforeach
            </ul>
          @else
            <p class="text-muted mb-0">No birthdays this month.</p>
          @endif
        </div>
      </div>
    </div>

    {{-- Who is out of Office (Sick) --}}
    <div class="col-xl-4 mb-4">
      <div class="card shadow h-100">
        <div class="card-header"><strong>Who is out of Office</strong> <small class="text-muted">• Sick Leave</small></div>
        <div class="card-body">
          @if(($sickLeaves ?? collect())->isNotEmpty())
            <ul class="list-unstyled klean mb-0">
              @foreach($sickLeaves as $lr)
                <li>
                  <img class="avatar-xs" src="{{ $lr->employee?->avatar_url ?? asset('images/avatar-default.png') }}" alt="">
                  <div>
                    <div class="font-weight-bold">{{ $lr->employee?->name ?? 'Employee' }}</div>
                    <div class="text-muted small">
                      {{ optional($lr->start_date)->format('d M') }} – {{ optional($lr->end_date)->format('d M Y') }}
                      @if(!empty($lr->days)) ({{ $lr->days }}d) @endif
                    </div>
                  </div>
                  <span class="ml-auto badge badge-warning">{{ ucfirst($lr->status ?? 'approved') }}</span>
                </li>
              @endforeach
            </ul>
          @else
            <p class="text-muted mb-0">No one is currently on sick leave.</p>
          @endif
        </div>
      </div>
    </div>

    {{-- Payments (Payrun) --}}
    <div class="col-xl-4 mb-4">
      <div class="card shadow h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>Payments</strong>
          <span class="badge badge-light">{{ now()->startOfMonth()->format('d M') }} - {{ now()->endOfMonth()->format('d M Y') }}</span>
        </div>
        <div class="card-body">
          @if(($payruns ?? collect())->isNotEmpty())
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead class="thead-light">
                  <tr>
                    <th>Date</th><th>Group</th><th>Total</th><th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($payruns as $pr)
                    @php
                      $sum = $pr->items->sum(function($it){
                        $a = $it->getAttributes();
                        foreach (['amount','total','value','net_amount','gross_amount','nominal'] as $k) {
                          if (array_key_exists($k,$a) && is_numeric($a[$k])) return (float)$a[$k];
                        }
                        return 0;
                      });
                      $st = strtolower($pr->status ?? (filled($pr->finalized_at)?'finalized':'draft'));
                    @endphp
                    <tr>
                      <td>{{ optional($pr->finalized_at ?? $pr->end_date ?? $pr->created_at)->format('d M') }}</td>
                      <td>{{ $pr->payGroup?->name ?? '-' }}</td>
                      <td>{{ number_format($sum) }}</td>
                      <td>
                        <span class="badge badge-{{ in_array($st,['processed','finalized'])?'success':($st==='pending'?'warning':($st==='failed'?'danger':'secondary')) }}">
                          {{ ucfirst($st) }}
                        </span>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <p class="text-muted mb-0">No payruns to display.</p>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- ===================== Right Column: Calendar + News/Holidays ===================== --}}
  <div class="row">
    <div class="col-xl-4 order-xl-2 mb-4">
      <div class="card shadow h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>{{ now()->format('F Y') }}</strong>
          <span class="text-muted small">auto</span>
        </div>
        <div class="card-body">
          @php
            $today = now();
            $start = $today->copy()->startOfMonth()->startOfWeek(\Carbon\Carbon::SUNDAY);
            $end   = $today->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SATURDAY);
          @endphp
          <div class="mini-calendar d-flex flex-wrap">
            @for($d=$start->copy(); $d->lte($end); $d->addDay())
              @php
                $isCurrentMonth = $d->month === $today->month;
                $cls = 'day '.($isCurrentMonth?'':'muted').' '.($d->isSameDay($today)?'today':'');
              @endphp
              <div class="{{ $cls }}">{{ $d->day }}</div>
            @endfor
          </div>
        </div>
        <div class="card-footer">
          <strong>News & Holidays</strong>
          <ul class="list-unstyled klean mb-0 mt-2">
            @forelse(($holidays ?? collect()) as $h)
              <li>
                <i class="far fa-calendar-check mt-1"></i>
                <div>
                  <div class="font-weight-bold">{{ $h->title }}</div>
                  <div class="text-muted small">{{ optional($h->date)->format('D, d M Y') }}</div>
                </div>
              </li>
            @empty
              <li class="text-muted">No upcoming holidays.</li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>

    {{-- Slot kiri lebar (bisa dipakai untuk chart/heatmap di masa depan) --}}
    <div class="col-xl-8 order-xl-1 mb-4">
      <div class="alert alert-light border mb-0">
        Anda berada di ruang <strong>SuperAdmin</strong>. Semua metrik bersifat global.
        Gunakan <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a> untuk operasi HR & Payroll detail.
      </div>
    </div>
  </div>
@endsection
