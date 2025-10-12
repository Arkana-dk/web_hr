<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CompanyBankAccount;

class CompanyBankAccountController extends Controller
{
    public function index()
    {
        $banks = CompanyBankAccount::orderBy('is_default','desc')
            ->orderBy('bank_name')
            ->get();

        return response()->json($banks);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bank_name'            => 'required|string|max:255',
            'bank_account_number'  => 'required|string|max:50',
            'bank_account_owner'   => 'required|string|max:255',
            'is_default'           => 'sometimes|boolean',
        ]);

        if (! empty($data['is_default'])) {
            CompanyBankAccount::where('is_default',true)
                ->update(['is_default'=>false]);
        }

        $bank = CompanyBankAccount::create([
            'bank_name'            => $data['bank_name'],
            'bank_account_number'  => $data['bank_account_number'],
            'bank_account_owner'   => $data['bank_account_owner'],
            'is_default'           => !empty($data['is_default']),
        ]);

        return response()->json($bank, 201);
    }

    public function show($id)
    {
        $bank = CompanyBankAccount::findOrFail($id);
        return response()->json($bank);
    }

    public function update(Request $request, $id)
    {
        $bank = CompanyBankAccount::findOrFail($id);

        $data = $request->validate([
            'bank_name'            => 'required|string|max:255',
            'bank_account_number'  => 'required|string|max:50',
            'bank_account_owner'   => 'required|string|max:255',
            'is_default'           => 'sometimes|boolean',
        ]);

        if (! empty($data['is_default'])) {
            CompanyBankAccount::where('is_default',true)
                ->update(['is_default'=>false]);
        }

        $bank->update([
            'bank_name'            => $data['bank_name'],
            'bank_account_number'  => $data['bank_account_number'],
            'bank_account_owner'   => $data['bank_account_owner'],
            'is_default'           => !empty($data['is_default']),
        ]);

        return response()->json($bank);
    }

    public function destroy($id)
    {
        $bank = CompanyBankAccount::findOrFail($id);
        $bank->delete();
        return response()->json(null, 204);
    }
}
