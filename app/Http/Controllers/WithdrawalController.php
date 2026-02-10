<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

use App\Models\WithdrawalRequest as WithdrawalRequestModel;
use App\Models\Wallet;
use App\Models\WithdrawalBankAccount;

class WithdrawalController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $wallets = $user->wallets()
            ->whereIn('type', ['main', 'chips', 'bonus'])
            ->get()
            ->keyBy('type');

        $cash  = $wallets->get('main')?->balance ?? 0;
        $chips = $wallets->get('chips')?->balance ?? 0;
        $bonus = $wallets->get('bonus')?->balance ?? 0;

        $currency = $user->currency ?? 'MYR';

        $banks = [
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

        $bankAccounts = $user->withdrawalBankAccounts()
            ->latest()
            ->get();

        $defaultBankAccount = null;
        if ($user->default_withdrawal_bank_account_id) {
            $defaultBankAccount = $bankAccounts->firstWhere('id', $user->default_withdrawal_bank_account_id)
                ?: $user->withdrawalBankAccounts()
                    ->where('id', $user->default_withdrawal_bank_account_id)
                    ->first();
        }

        $history = WithdrawalRequestModel::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('withdrawals.index', [
            'title' => 'Withdrawal',

            'cash' => $cash,
            'chips' => $chips,
            'bonus' => $bonus,
            'currency' => $currency,

            'banks' => $banks,
            'bankAccounts' => $bankAccounts,
            'defaultBankAccount' => $defaultBankAccount,

            'history' => $history,

            'minWithdraw' => 100,
            'maxWithdraw' => 99999999.99,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $currency = $user->currency ?? 'MYR';

        $request->merge([
            'amount' => is_string($request->input('amount'))
                ? str_replace([',', ' '], '', $request->input('amount'))
                : $request->input('amount'),
        ]);

        $data = $request->validate([
            'bank_account_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:100', 'max:99999999.99'],
        ]);

        $bankAccount = WithdrawalBankAccount::query()
            ->where('id', $data['bank_account_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$bankAccount) {
            return back()
                ->withErrors(['withdraw' => 'Invalid bank account selected.'])
                ->withInput();
        }

        $amountStr = (string) $data['amount'];
        $amountCents = $this->moneyToCents($amountStr);

        if ($amountCents <= 0) {
            return back()
                ->withErrors(['amount' => 'Invalid amount.'])
                ->withInput();
        }

        try {
            DB::transaction(function () use ($user, $currency, $bankAccount, $amountCents, $amountStr) {
                $cashWallet = $user->wallets()
                    ->where('type', Wallet::TYPE_MAIN)
                    ->lockForUpdate()
                    ->first();

                if (!$cashWallet) {
                    throw new \RuntimeException('Cash wallet not found.');
                }

                $balanceCents = $this->moneyToCents((string) $cashWallet->balance);

                if ($amountCents > $balanceCents) {
                    throw new \RuntimeException('Insufficient cash balance.');
                }

                $newBalanceCents = $balanceCents - $amountCents;
                $cashWallet->balance = $this->centsToMoney($newBalanceCents);
                $cashWallet->save();

                WithdrawalRequestModel::create([
                    'user_id' => $user->id,
                    'bank_account_id' => $bankAccount->id,
                    'currency' => $currency,
                    'amount' => $amountStr,
                    'status' => 'pending',
                    'remarks' => null,
                ]);
            }, 3);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();

            if ($msg === 'Insufficient cash balance.') {
                return back()
                    ->withErrors(['amount' => 'Insufficient cash balance.'])
                    ->withInput();
            }

            return back()
                ->withErrors(['withdraw' => $msg ?: 'Withdrawal failed. Please try again.'])
                ->withInput();
        }

        return back()->with('success', 'Withdrawal submitted.');
    }

    private function moneyToCents(string $v): int
    {
        $s = trim($v);
        $s = str_replace([',', ' '], '', $s);

        if ($s === '') return 0;

        $neg = false;
        if (str_starts_with($s, '-')) {
            $neg = true;
            $s = substr($s, 1);
        }

        if (!preg_match('/^\d+(\.\d+)?$/', $s)) {
            return 0;
        }

        [$whole, $dec] = array_pad(explode('.', $s, 2), 2, '0');
        $dec = substr(str_pad($dec, 2, '0'), 0, 2);

        $cents = ((int) $whole) * 100 + (int) $dec;
        return $neg ? -$cents : $cents;
    }

    private function centsToMoney(int $cents): string
    {
        $neg = $cents < 0;
        $cents = abs($cents);

        $whole = intdiv($cents, 100);
        $dec = str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);

        return ($neg ? '-' : '') . $whole . '.' . $dec;
    }
}