@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <h4 class="mb-4">Tambah Seksi</h4>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.sections.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label">Nama Seksi</label>
            <input type="text" class="form-control" id="name" name="name" placeholder="Contoh: Curing" required>
        </div>

        <div class="mb-3">
            <label for="department_id" class="form-label">Departemen</label>
            <select name="department_id" id="department_id" class="form-select" required>
                <option value="">-- Pilih Departemen --</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('admin.sections.index') }}" class="btn btn-secondary">Batal</a>
    </form>
</div>
@endsection
