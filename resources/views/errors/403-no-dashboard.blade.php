@extends('layouts.master')
@section('title','Forbidden')

@section('content')
<div class="text-center py-5">
  <h4 class="mb-2">403 – No dashboard configured for your role</h4>
  <p class="text-muted mb-4">Akunmu belum di-mapping ke dashboard mana pun.</p>

  <form action="{{ route('logout') }}" method="POST" class="d-inline">
    @csrf
    <button class="btn btn-outline-danger rounded-pill">Logout</button>
  </form>
</div>
@endsection
