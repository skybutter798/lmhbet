<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalBankAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WithdrawalBankAccountController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $banks = $this->malaysiaBanks();

        $accounts = $user->withdrawalBankAccounts()
            ->latest()
            ->get();

        $defaultId = $user->default_withdrawal_bank_account_id;

        return view('profile.bank', [
            'title' => 'Bank Details',
            'banks' => $banks,
            'accounts' => $accounts,
            'defaultId' => $defaultId,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $banks = $this->malaysiaBanks();

        $data = $request->validate([
            'bank_name' => ['required', 'string', Rule::in($banks)],
            'account_holder_name' => ['required', 'string', 'max:191'],
            'account_number' => ['required', 'string', 'max:64', 'regex:/^[0-9]+$/'],
        ], [
            'account_number.regex' => 'Account number must contain digits only.',
        ]);

        $last4 = strlen($data['account_number']) >= 4
            ? substr($data['account_number'], -4)
            : $data['account_number'];

        $acc = $user->withdrawalBankAccounts()->create([
            'bank_name' => $data['bank_name'],
            'account_holder_name' => $data['account_holder_name'],
            'account_number' => $data['account_number'], // encrypted cast
            'account_last4' => $last4,
        ]);

        // If user has no default yet, make this default
        if (!$user->default_withdrawal_bank_account_id) {
            $user->default_withdrawal_bank_account_id = $acc->id;
            $user->save();
        }

        return back()->with('success', 'Bank account added.');
    }

    public function setDefault(WithdrawalBankAccount $bankAccount)
    {
        $user = auth()->user();

        if ((int)$bankAccount->user_id !== (int)$user->id) {
            abort(403);
        }

        $user->default_withdrawal_bank_account_id = $bankAccount->id;
        $user->save();

        return back()->with('success', 'Default bank account updated.');
    }

    public function destroy(WithdrawalBankAccount $bankAccount)
    {
        $user = auth()->user();

        if ((int)$bankAccount->user_id !== (int)$user->id) {
            abort(403);
        }

        $isDefault = (int)$user->default_withdrawal_bank_account_id === (int)$bankAccount->id;

        $bankAccount->delete();

        if ($isDefault) {
            $next = $user->withdrawalBankAccounts()->latest()->first();
            $user->default_withdrawal_bank_account_id = $next?->id;
            $user->save();
        }

        return back()->with('success', 'Bank account removed.');
    }

    private function malaysiaBanks(): array
    {
        return [
            'Affin Bank',
            'Agrobank',
            'Alliance Bank',
            'AmBank',
            'Bank Islam',
            'Bank Muamalat',
            'Bank Rakyat',
            'Bank Simpanan Nasional (BSN)',
            'CIMB Bank',
            'Citibank Malaysia',
            'Hong Leong Bank',
            'HSBC Bank Malaysia',
            'Kuwait Finance House',
            'Maybank',
            'OCBC Bank Malaysia',
            'Public Bank',
            'RHB Bank',
            'Standard Chartered Bank Malaysia',
            'UOB Malaysia',
        ];
    }
}