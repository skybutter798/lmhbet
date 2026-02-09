<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $wallets = $user->wallets()
            ->whereIn('type', ['main', 'chips', 'bonus'])
            ->get()
            ->keyBy('type');

        $cash  = (float)($wallets->get('main')?->balance ?? 0);
        $chips = (float)($wallets->get('chips')?->balance ?? 0);
        $bonus = (float)($wallets->get('bonus')?->balance ?? 0);

        $txToday = WalletTransaction::query()
            ->where('user_id', $user->id)
            ->where('title', 'transfer')
            ->whereDate('created_at', now()->toDateString())
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        return view('transfer.index', [
            'title' => 'Transfer',

            'currency' => $user->currency ?? 'MYR',
            'cash' => $cash,
            'chips' => $chips,
            'bonus' => $bonus,

            'walletOptions' => [
                'main' => 'Cash',
                'chips' => 'Chips',
                'bonus' => 'Bonus',
            ],

            'txToday' => $txToday,
        ]);
    }

    public function store(Request $request)
    {
        $fromUser = auth()->user();

        $data = $request->validate([
            'to_username' => ['required', 'string', 'max:50'],
            'wallet_type' => ['required', Rule::in(['main','chips','bonus'])],
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => ['nullable', 'string', 'max:200'],
        ]);

        $toUser = User::where('username', $data['to_username'])->first();
        if (!$toUser) {
            return back()->withErrors(['to_username' => 'User not found.'])->withInput();
        }
        if ($toUser->id === $fromUser->id) {
            return back()->withErrors(['to_username' => 'Cannot transfer to yourself.'])->withInput();
        }

        return DB::transaction(function () use ($fromUser, $toUser, $data) {
            // lock wallets
            $fromWallet = Wallet::where('user_id', $fromUser->id)
                ->where('type', $data['wallet_type'])
                ->lockForUpdate()
                ->first();

            $toWallet = Wallet::where('user_id', $toUser->id)
                ->where('type', $data['wallet_type'])
                ->lockForUpdate()
                ->first();

            if (!$fromWallet || !$toWallet) {
                return back()->withErrors(['wallet_type' => 'Wallet not found.'])->withInput();
            }

            $amount = (float)$data['amount'];
            $before = (float)$fromWallet->balance;

            if ($before < $amount) {
                return back()->withErrors(['amount' => 'Insufficient balance.'])->withInput();
            }

            // debit sender
            $fromWallet->balance = $before - $amount;
            $fromWallet->save();

            // credit receiver
            $toBefore = (float)$toWallet->balance;
            $toWallet->balance = $toBefore + $amount;
            $toWallet->save();

            $ref = 'TRF-' . strtoupper(bin2hex(random_bytes(6)));

            // transaction record (sender)
            WalletTransaction::create([
                'user_id' => $fromUser->id,
                'wallet_id' => $fromWallet->id,
                'wallet_type' => $fromWallet->type,
                'direction' => WalletTransaction::DIR_DEBIT,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => (float)$fromWallet->balance,
                'status' => WalletTransaction::STATUS_COMPLETED,
                'reference' => $ref,
                'title' => 'transfer',
                'description' => $data['description'] ?: ('Transfer to ' . $toUser->username),
                'ip' => request()->ip(),
                'user_agent' => substr((string)request()->userAgent(), 0, 255),
                'occurred_at' => now(),
                'meta' => [
                    'to_user_id' => $toUser->id,
                    'to_username' => $toUser->username,
                ],
            ]);

            // transaction record (receiver)
            WalletTransaction::create([
                'user_id' => $toUser->id,
                'wallet_id' => $toWallet->id,
                'wallet_type' => $toWallet->type,
                'direction' => WalletTransaction::DIR_CREDIT,
                'amount' => $amount,
                'balance_before' => $toBefore,
                'balance_after' => (float)$toWallet->balance,
                'status' => WalletTransaction::STATUS_COMPLETED,
                'reference' => $ref,
                'title' => 'transfer',
                'description' => $data['description'] ?: ('Received from ' . $fromUser->username),
                'ip' => request()->ip(),
                'user_agent' => substr((string)request()->userAgent(), 0, 255),
                'occurred_at' => now(),
                'meta' => [
                    'from_user_id' => $fromUser->id,
                    'from_username' => $fromUser->username,
                ],
            ]);

            return redirect()->route('transfer.index')->with('success', 'Transfer completed.');
        });
    }
}
