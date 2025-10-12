<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyBankAccount;
use Illuminate\Http\Request;

class CompanyBankAccountController extends Controller
{
    public function index()
    {
        $banks = CompanyBankAccount::orderBy('is_default', 'desc')->orderBy('bank_name')->get();
        return view('admin.pages.companybankaccount', compact('banks'));
    }

    public function create()
    {
        return view('admin.pages.companybankaccount.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:50',
            'bank_account_owner' => 'required|string|max:255',
            'is_default' => 'nullable|boolean',
        ]);

        // Jika default → reset default lain
        if ($request->is_default) {
            CompanyBankAccount::where('is_default', true)->update(['is_default' => false]);
        }

        CompanyBankAccount::create([
            'bank_name' => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
            'bank_account_owner' => $request->bank_account_owner,
            'is_default' => $request->is_default ? true : false,
        ]);

        return redirect()->route('admin.companybankaccount.index')->with('success', 'Bank perusahaan berhasil ditambahkan.');
    }

    public function edit(CompanyBankAccount $company_bank_account)
    {
        return view('admin.pages.companybankaccount.edit', compact('company_bank_account'));
    }

    public function update(Request $request, CompanyBankAccount $company_bank_account)
    {
        $request->validate([
            'bank_name' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:50',
            'bank_account_owner' => 'required|string|max:255',
            'is_default' => 'nullable|boolean',
        ]);

        if ($request->is_default) {
            CompanyBankAccount::where('is_default', true)->update(['is_default' => false]);
        }

        $company_bank_account->update([
            'bank_name' => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
            'bank_account_owner' => $request->bank_account_owner,
            'is_default' => $request->is_default ? true : false,
        ]);

        return redirect()->route('admin.companybankaccount.index')->with('success', 'Bank perusahaan berhasil diperbarui.');
    }

    public function destroy(CompanyBankAccount $company_bank_account)
    {
        $company_bank_account->delete();
        return redirect()->route('admin.companybankaccount.index')->with('success', 'Bank perusahaan berhasil dihapus.');
    }
}
