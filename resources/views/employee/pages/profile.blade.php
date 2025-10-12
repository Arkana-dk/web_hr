{{-- resources/views/employee/pages/profile.blade.php --}}
@extends('layouts.master')

@section('title','Profil Saya')

@section('content')
<div class="container-fluid">
  <div class="row justify-content-center">
    <div class="col-lg-8">

      <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white d-flex align-items-center">
          <i class="fas fa-user-circle fa-2x mr-2"></i>
          <h5 class="m-0">Profil Saya</h5>
        </div>
        <div class="card-body d-flex">
          {{-- Foto 3x4 --}}
          <div class="pr-4">
            @if($employee && $employee->photo)
              <img src="{{ asset('storage/'.$employee->photo) }}"
                   alt="Foto {{ $user->name }}"
                   style="width:180px; height:240px; object-fit:cover; border:1px solid #ddd; border-radius:4px;">
            @else
              <div class="bg-secondary text-white d-flex align-items-center justify-content-center"
                   style="width:180px; height:240px;">
                <i class="fas fa-user fa-5x"></i>
              </div>
            @endif
          </div>

          {{-- Data --}}
          <div class="flex-fill">
            <table class="table table-borderless mb-0">
              <tr>
                <th width="30%">Nama Lengkap</th>
                <td>{{ $user->name }}</td>
              </tr>
              <tr>
                <th>Email</th>
                <td>{{ $user->email }}</td>
              </tr>
              @if($employee)
              <tr>
                <th>Telepon</th>
                <td>{{ optional($employee->recruitment)->phone ?? '-' }}</td>
              </tr>
              <tr>
                <th>Department</th>
                <td>{{ optional($employee->department)->name ?? '-' }}</td>
              </tr>
              <tr>
                <th>Posisi</th>
                <td>{{ optional($employee->position)->name ?? '-' }}</td>
              </tr>
              <tr>
                <th>Title</th>
                <td>{{ $employee->title ?? '-' }}</td>
              </tr>
              <tr>
                <th>Tanggal Lahir</th>
                <td>
                  {{ optional($employee->recruitment)->date_of_birth
                     ? \Carbon\Carbon::parse($employee->recruitment->date_of_birth)
                         ->translatedFormat('d F Y')
                     : '-' }}
                </td>
              </tr>
              @endif
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection
