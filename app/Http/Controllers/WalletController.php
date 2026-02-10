<?php

namespace App\Http\Controllers;

use App\Models\DepositRequest;
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

        // Only real wallets you want to show/use
        $wallets = $user->wallets()
            ->whereIn('type', ['main', 'chips'])
            ->get()
            ->keyBy('type');

        $bonusRecords = DepositRequest::query()
            ->with(['promotion:id,title,turnover_multiplier'])
            ->where('user_id', $user->id)
            ->whereNotNull('promotion_id')
            ->where('status', DepositRequest::STATUS_APPROVED)
            ->whereIn('bonus_status', ['in_progress', 'done'])
            ->orderByDesc('paid_at')
            ->limit(50)
            ->get();

        // ✅ Pending bonus preparing to release
        $pendingBonus = (float) DepositRequest::query()
            ->where('user_id', $user->id)
            ->whereNotNull('promotion_id')
            ->where('status', DepositRequest::STATUS_APPROVED)
            ->where('bonus_status', 'in_progress')
            ->sum('bonus_amount');

        return view('wallets.index', [
            'title' => 'Wallet',
            'cash'  => $wallets->get('main')?->balance ?? 0,
            'chips' => $wallets->get('chips')?->balance ?? 0,

            // now bonus = pending promo bonus, NOT wallet(type=bonus)
            'bonus' => $pendingBonus,

            'currency' => $user->currency ?? 'MYR',
            'bonusRecords' => $bonusRecords,
        ]);
    }

    /**
     * ✅ Internal transfer only:
     * chips -> main
     * main  -> chips
     *
     * (bonus wallet transfer removed because "bonus" is now pending promo display)
     */
    public function transferInternal(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'from'   => ['required', 'in:main,chips'],
            'to'     => ['required', 'in:main,chips'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $from = $data['from'];
        $to   = $data['to'];

        $allowed = [
            'chips:main',
            'main:chips',
        ];

        if ($from === $to || !in_array("{$from}:{$to}", $allowed, true)) {
            throw ValidationException::withMessages([
                'to' => 'This transfer is not allowed.',
            ]);
        }

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
            $wallets = $user->wallets()
                ->whereIn('type', [$from, $to])
                ->lockForUpdate()
                ->get()
                ->keyBy('type');

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

            $fromWallet->balance = $this->centsToMoney($fromAfterCents);
            $toWallet->balance   = $this->centsToMoney($toAfterCents);

            $fromWallet->save();
            $toWallet->save();

            $pairLabel = strtoupper($from) . ' -> ' . strtoupper($to);

            WalletTransaction::create([
                'user_id' => $user->id,
                'wallet_id' => $fromWallet->id,
                'wallet_type' => $from,
                'direction' => WalletTransaction::DIR_DEBIT,
                'amount' => $this->centsToMoney($amtCents),
                'balance_before' => $this->centsToMoney($fromBeforeCents),
                'balance_after' => $this->centsToMoney($fromAfterCents),
                'status' => WalletTransaction::STATUS_COMPLETED,
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
        return (int) round(((float) $value) * 100);
    }

    private function centsToMoney(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}