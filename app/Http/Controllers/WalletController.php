<?php

// app/Http/Controllers/WalletController.php

namespace App\Http\Controllers;

use App\Models\BetRecord;
use App\Models\DepositRequest;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

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

        $pendingBonus = (float) DepositRequest::query()
            ->where('user_id', $user->id)
            ->whereNotNull('promotion_id')
            ->where('status', DepositRequest::STATUS_APPROVED)
            ->where('bonus_status', 'in_progress')
            ->sum('bonus_amount');

        // =========================
        // BETTING SUMMARY + DATE FILTER
        // URL: /wallet?bet_from=2026-01-01&bet_to=2026-01-31
        // =========================
        $v = $request->validate([
            'bet_from' => ['nullable', 'date'],
            'bet_to'   => ['nullable', 'date', 'after_or_equal:bet_from'],
        ]);

        $betQ = BetRecord::query()->where('user_id', $user->id);

        if (!empty($v['bet_from'])) {
            $betQ->where('bet_at', '>=', Carbon::parse($v['bet_from'])->startOfDay());
        }
        if (!empty($v['bet_to'])) {
            $betQ->where('bet_at', '<=', Carbon::parse($v['bet_to'])->endOfDay());
        }

        $betSummary = $betQ->selectRaw('
            COUNT(*) AS total_bets,
            SUM(CASE WHEN status = "settled" THEN 1 ELSE 0 END) AS settled_bets,
            SUM(CASE WHEN status = "open" THEN 1 ELSE 0 END) AS open_bets,

            COALESCE(SUM(stake_amount), 0) AS total_turnover,

            COALESCE(SUM(CASE WHEN status = "settled" THEN payout_amount ELSE 0 END), 0) AS total_payout,
            COALESCE(SUM(CASE WHEN status = "settled" THEN profit_amount ELSE 0 END), 0) AS net_profit,

            MIN(bet_at) AS first_bet_at,
            MAX(bet_at) AS last_bet_at,
            MAX(settled_at) AS last_settled_at,

            SUM(CASE WHEN status = "settled" AND profit_amount > 0 THEN 1 ELSE 0 END) AS win_bets,
            SUM(CASE WHEN status = "settled" AND profit_amount < 0 THEN 1 ELSE 0 END) AS lose_bets,
            SUM(CASE WHEN status = "settled" AND profit_amount = 0 THEN 1 ELSE 0 END) AS draw_bets
        ')->first();

        return view('wallets.index', [
            'title' => 'Wallet',
            'cash'  => $wallets->get('main')?->balance ?? 0,
            'chips' => $wallets->get('chips')?->balance ?? 0,
            'bonus' => $pendingBonus,
            'currency' => $user->currency ?? 'MYR',
            'bonusRecords' => $bonusRecords,

            'betSummary' => $betSummary,
            'betFrom' => $v['bet_from'] ?? null,
            'betTo'   => $v['bet_to'] ?? null,
        ]);
    }

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

        DB::transaction(function () use ($user, $from, $to, $amtCents, $groupRef, $ip, $ua, $occurredAt) {
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
