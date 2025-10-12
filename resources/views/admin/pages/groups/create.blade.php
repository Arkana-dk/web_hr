@extends('layouts.master')

@section('content')
<div class="container">
    <h4>Tambah Group</h4>

    <form action="{{ route('admin.groups.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label>Nama Group</label>
            <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
        </div>

        <button type="submit" class="btn btn-primary mt-3">Simpan</button>
        <a href="{{ route('admin.groups.index') }}" class="btn btn-secondary mt-3">Batal</a>
    </form>
</div>
@endsection
