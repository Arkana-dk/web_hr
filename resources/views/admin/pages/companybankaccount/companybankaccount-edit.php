@extends('layouts.master')

@section('title','Edit Bank Perusahaan')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Edit Bank Perusahaan</h1>

    <form action="{{ route('admin.company_bank_accounts.update', $company_bank_account->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label>Nama Bank</label>
            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $company_bank_account->bank_name) }}" required>
        </div>

        <div class="form-group">
            <label>Nomor Rekening</label>
            <input type="text" name="bank_account_number" class="form-control" value="{{ old('bank_account_number', $company_bank_account->bank_account_number) }}" required>
        </div>

        <div class="form-group">
            <label>Nama Pemilik Rekening</label>
            <input type="text" name="bank_account_owner" class="form-control" value="{{ old('bank_account_owner', $company_bank_account->bank_account_owner) }}" required>
        </div>

        <div class="form-group form-check">
            <input type="checkbox" name="is_default" value="1" class="form-check-input" id="defaultCheck"
            {{ old('is_default', $company_bank_account->is_default) ? 'checked' : '' }}>
            <label class="form-check-label" for="defaultCheck">Set Sebagai Default</label>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ route('admin.company_bank_accounts.index') }}" class="btn btn-secondary">Batal</a>
    </form>
</div>
@endsection
