@extends('layouts.master')
@section('title','Edit Jadwal')

@section('content')
<div class="container-fluid">
  <h1 class="h3 text-gray-800 mb-4">Edit Jadwal</h1>
  <div class="card mb-4">
    <div class="card-header">Edit Jadwal</div>
    <div class="card-body">
      <form action="{{ route('admin.calendars.update', $calendar) }}"
            method="POST"
            class="form-inline">
        @csrf
        @method('PUT')

        <div class="form-group mr-2">
          <label class="mr-1">Tanggal:</label>
          <input type="date" name="date" class="form-control"
                 value="{{ old('date', $calendar->date->toDateString()) }}"
                 required>
        </div>

        <div class="form-group mr-2">
          <label class="mr-1">Keterangan:</label>
          <input type="text" name="description" class="form-control"
                 value="{{ old('description', $calendar->description) }}"
                 required>
        </div>

        <div class="form-group mr-2">
          <label class="mr-1">Tipe:</label>
          <select name="type" class="form-control" required>
            <option value="Masuk"   @selected($calendar->type=='Masuk')>Masuk</option>
            <option value="Warning" @selected($calendar->type=='Warning')>Warning</option>
            <option value="Libur"   @selected($calendar->type=='Libur')>Libur</option>
          </select>
        </div>

        <div class="form-group mr-2">
          <label class="mr-1">Warna:</label>
          <input type="color" name="color" class="form-control"
                 value="{{ old('color', $calendar->color) }}">
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ route('admin.calendars.settings') }}" class="btn btn-secondary ml-2">Batal</a>
      </form>
    </div>
  </div>
</div>
@endsection
