@extends('layouts.master')

@section('title','Edit Kebijakan Cuti')

@section('content')
<div class="container">
    <div class="card soft">
        <div class="card-header">
            <h5 class="mb-0">Edit Kebijakan Cuti</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.leave-policies.update', $policy->id) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- gunakan partial form --}}
                @include('admin.pages.leave-policies._form', ['policy' => $policy, 'leaveTypes' => $leaveTypes])

                
            </form>
        </div>
    </div>
</div>
@endsection
