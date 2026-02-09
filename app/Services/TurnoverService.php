<?php

namespace App\Services;

use App\Models\BetRecord;
use App\Models\BonusTurnoverItem;
use App\Models\DepositRequest;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TurnoverService
{
    public function applySettledBet(BetRecord $bet): void
    {
        if (!$this->isSettled($bet)) return;

        $stake = (float) $bet->stake_amount;
        if ($stake <= 0) return;

        if (BonusTurnoverItem::where('bet_record_id', $bet->id)->exists()) {
            return;
        }

        DB::transaction(function () use ($bet, $stake) {
            $exists = BonusTurnoverItem::where('bet_record_id', $bet->id)->lockForUpdate()->exists();
            if ($exists) return;

            /** @var DepositRequest|null $bonus */
            $bonus = DepositRequest::query()
                ->where('user_id', $bet->user_id)
                ->where('status', DepositRequest::STATUS_APPROVED)
                ->whereNotNull('promotion_id')
                ->where('bonus_status', 'in_progress')
                ->orderBy('paid_at')
                ->lockForUpdate()
                ->first();

            if (!$bonus) return;

            if ($bonus->paid_at && $bet->bet_at && $bet->bet_at->lt($bonus->paid_at)) {
                return;
            }

            $promo = $bonus->promotion()->with(['dboxProviders:id,name'])->first();
            if ($promo && $promo->relationLoaded('dboxProviders') && $promo->dboxProviders->count() > 0) {
                $allowedNames = $promo->dboxProviders->pluck('name')->map(fn($x) => (string)$x)->all();
                $betProv = (string) ($bet->provider ?? '');
                if ($betProv === '' || !in_array($betProv, $allowedNames, true)) {
                    return;
                }
            }

            $required = (float) ($bonus->turnover_required ?? 0);
            $progress = (float) ($bonus->turnover_progress ?? 0);
            if ($required <= 0) return;

            $remaining = max(0, $required - $progress);
            if ($remaining <= 0) {
                $this->completeBonusIfNeeded($bonus);
                return;
            }

            $counted = min($stake, $remaining);

            BonusTurnoverItem::create([
                'deposit_request_id' => $bonus->id,
                'bet_record_id' => $bet->id,
                'counted_amount' => number_format($counted, 2, '.', ''),
            ]);

            $bonus->turnover_progress = number_format($progress + $counted, 2, '.', '');
            $bonus->save();

            $this->completeBonusIfNeeded($bonus);
        });
    }

    private function completeBonusIfNeeded(DepositRequest $bonus): void
    {
        $bonus->refresh();

        if (($bonus->bonus_status ?? '') !== 'in_progress') return;

        $required = (float) ($bonus->turnover_required ?? 0);
        $progress = (float) ($bonus->turnover_progress ?? 0);

        if ($required <= 0) return;
        if ($progress + 1e-9 < $required) return;

        DB::transaction(function () use ($bonus) {
            $bonus = DepositRequest::where('id', $bonus->id)->lockForUpdate()->first();
            if (!$bonus) return;
            if (($bonus->bonus_status ?? '') !== 'in_progress') return;

            $amount = (float) ($bonus->bonus_amount ?? 0);

            // mark done even if bonus = 0
            $bonus->bonus_status = 'done';
            $bonus->bonus_done_at = now();
            $bonus->save();

            if ($amount <= 0) return;

            $wMain = Wallet::firstOrCreate(
                ['user_id' => $bonus->user_id, 'type' => Wallet::TYPE_MAIN],
                ['balance' => '0', 'status' => Wallet::STATUS_ACTIVE]
            );

            $wMain = Wallet::where('id', $wMain->id)->lockForUpdate()->first();

            $before = (float) $wMain->balance;
            $after  = $before + $amount;

            $wMain->balance = number_format($after, 2, '.', '');
            $wMain->save();

            $ref = 'BONUS-' . ($bonus->reference ?: Str::uuid()->toString());

            WalletTransaction::create([
                'user_id' => $bonus->user_id,
                'wallet_id' => $wMain->id,
                'wallet_type' => Wallet::TYPE_MAIN,
                'direction' => WalletTransaction::DIR_CREDIT,
                'amount' => number_format($amount, 2, '.', ''),
                'balance_before' => number_format($before, 2, '.', ''),
                'balance_after' => number_format($after, 2, '.', ''),
                'status' => WalletTransaction::STATUS_COMPLETED,
                'reference' => $ref,
                'provider' => 'PROMO',
                'round_ref' => $ref,
                'title' => 'Promotion Bonus Released',
                'description' => 'Turnover completed',
                'meta' => [
                    'deposit_request_id' => $bonus->id,
                    'promotion_id' => $bonus->promotion_id,
                    'deposit_reference' => $bonus->reference,
                ],
                'occurred_at' => now(),
            ]);
        });
    }

    private function isSettled(BetRecord $bet): bool
    {
        $s = $bet->status;
        if (is_string($s)) return $s === 'settled';
        if (is_numeric($s)) return (int)$s === 1;
        return false;
    }
}