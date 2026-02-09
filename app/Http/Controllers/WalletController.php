<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $wallets = $user->wallets()
            ->whereIn('type', ['main', 'chips', 'bonus'])
            ->get()
            ->keyBy('type');

        return view('wallets.index', [
            'title' => 'Wallet',
            'cash'  => $wallets->get('main')?->balance ?? 0,
            'chips' => $wallets->get('chips')?->balance ?? 0,
            'bonus' => $wallets->get('bonus')?->balance ?? 0,
            'currency' => $user->currency ?? 'MYR',
        ]);
    }

    /**
     * ✅ Internal transfer only:
     * chips -> main
     * main  -> chips
     * bonus -> chips
     */
    public function transferInternal(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'from'   => ['required', 'in:main,chips,bonus'],
            'to'     => ['required', 'in:main,chips'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $from = $data['from'];
        $to   = $data['to'];

        // Allowed pairs only
        $allowed = [
            'chips:main',
            'main:chips',
            'bonus:chips',
        ];

        if ($from === $to || !in_array("{$from}:{$to}", $allowed, true)) {
            throw ValidationException::withMessages([
                'to' => 'This transfer is not allowed.',
            ]);
        }

        // Normalize to 2dp (your WalletTransaction casts use decimal:2)
        $amtCents = $this->toCents($data['amount']);
        if ($amtCents <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Invalid amount.',
            ]);
        }

        $groupRef = 'ITR-' . Str::uuid()->toString();
        $ip = $request->ip();
        $ua = (string) $request->userAgent();
        $occurredAt = now();

        DB::transaction(function () use (
            $user,
            $from,
            $to,
            $amtCents,
            $groupRef,
            $ip,
            $ua,
            $occurredAt
        ) {
            // Lock involved wallets to prevent race conditions
            $wallets = $user->wallets()
                ->whereIn('type', [$from, $to])
                ->lockForUpdate()
                ->get()
                ->keyBy('type');

            // Ensure wallets exist
            $fromWallet = $wallets->get($from) ?: $user->wallets()->create([
                'type' => $from,
                'balance' => 0,
                'status' => Wallet::STATUS_ACTIVE,
            ]);

            $toWallet = $wallets->get($to) ?: $user->wallets()->create([
                'type' => $to,
                'balance' => 0,
                'status' => Wallet::STATUS_ACTIVE,
            ]);

            $fromBeforeCents = $this->toCents($fromWallet->balance);
            $toBeforeCents   = $this->toCents($toWallet->balance);

            if ($amtCents > $fromBeforeCents) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient balance.',
                ]);
            }

            $fromAfterCents = $fromBeforeCents - $amtCents;
            $toAfterCents   = $toBeforeCents + $amtCents;

            // Update balances
            $fromWallet->balance = $this->centsToMoney($fromAfterCents);
            $toWallet->balance   = $this->centsToMoney($toAfterCents);

            $fromWallet->save();
            $toWallet->save();

            $pairLabel = strtoupper($from) . ' -> ' . strtoupper($to);

            // ✅ Debit leg (unique reference)
            WalletTransaction::create([
                'user_id' => $user->id,
                'wallet_id' => $fromWallet->id,
                'wallet_type' => $from,
                'direction' => WalletTransaction::DIR_DEBIT,
                'amount' => $this->centsToMoney($amtCents),
                'balance_before' => $this->centsToMoney($fromBeforeCents),
                'balance_after' => $this->centsToMoney($fromAfterCents),
                'status' => WalletTransaction::STATUS_COMPLETED,

                // uniqueness-safe references
                'reference' => $groupRef . '-D',
                'provider'  => 'INT',
                'round_ref' => $groupRef,

                'title' => 'Internal Transfer',
                'description' => $pairLabel,
                'ip' => $ip,
                'user_agent' => $ua,
                'meta' => [
                    'kind' => 'internal_transfer',
                    'from' => $from,
                    'to' => $to,
                    'amount' => $this->centsToMoney($amtCents),
                ],
                'occurred_at' => $occurredAt,
            ]);

            // ✅ Credit leg (unique reference)
            WalletTransaction::create([
                'user_id' => $user->id,
                'wallet_id' => $toWallet->id,
                'wallet_type' => $to,
                'direction' => WalletTransaction::DIR_CREDIT,
                'amount' => $this->centsToMoney($amtCents),
                'balance_before' => $this->centsToMoney($toBeforeCents),
                'balance_after' => $this->centsToMoney($toAfterCents),
                'status' => WalletTransaction::STATUS_COMPLETED,

                'reference' => $groupRef . '-C',
                'provider'  => 'INT',
                'round_ref' => $groupRef,

                'title' => 'Internal Transfer',
                'description' => $pairLabel,
                'ip' => $ip,
                'user_agent' => $ua,
                'meta' => [
                    'kind' => 'internal_transfer',
                    'from' => $from,
                    'to' => $to,
                    'amount' => $this->centsToMoney($amtCents),
                ],
                'occurred_at' => $occurredAt,
            ]);
        });

        return redirect()
            ->route('wallet.index')
            ->with('success', 'Internal transfer completed.');
    }

    private function toCents($value): int
    {
        // normalize any input to 2dp cents
        return (int) round(((float) $value) * 100);
    }

    private function centsToMoney(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
