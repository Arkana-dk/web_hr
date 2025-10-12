@extends('layouts.master')

@section('title','Bank Perusahaan')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Bank Perusahaan</h1>

    <a href="{{ route('admin.company_bank_accounts.create') }}" class="btn btn-primary mb-3">Tambah Bank</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Bank</th>
                <th>No Rekening</th>
                <th>Nama Pemilik</th>
                <th>Default?</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($banks as $bank)
            <tr>
                <td>{{ $bank->bank_name }}</td>
                <td>{{ $bank->bank_account_number }}</td>
                <td>{{ $bank->bank_account_owner }}</td>
                <td>{{ $bank->is_default ? 'Ya' : 'Tidak' }}</td>
                <td>
                    <a href="{{ route('admin.company_bank_accounts.edit', $bank->id) }}" class="btn btn-warning btn-sm">Edit</a>
                    <form action="{{ route('admin.company_bank_accounts.destroy', $bank->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin hapus?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
