@extends('layouts.master')

@section('title','Tambah Kebijakan Cuti')
@include('components.leave.styles-soft')


@section('content')
<div class="container">
    <div class="card soft">
        <div class="card-header">
            <h5 class="mb-0">Tambah Kebijakan Cuti</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.leave-policies.store') }}" method="POST">
                @csrf
                @include('admin.pages.leave-policies._form')
                
            </form>
        </div>
    </div>
</div>
@endsection
