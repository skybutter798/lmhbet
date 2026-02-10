<?php
// /home/lmh/app/app/Http/Controllers/WalletBalanceController.php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\DepositRequest;
use Illuminate\Http\Request;

class WalletBalanceController extends Controller
{
    private const SCALE = 2;

    private function bc(string $n): string
    {
        if (function_exists('bcadd')) return bcadd($n, '0', self::SCALE);
        return number_format((float) $n, self::SCALE, '.', '');
    }

    private function blcNum(string $blc): float
    {
        return (float) $this->bc($blc);
    }

    private function getWallet(Request $request, string $type): Wallet
    {
        $user = $request->user();

        return Wallet::firstOrCreate(
            ['user_id' => $user->id, 'type' => $type],
            ['balance' => '0', 'status' => Wallet::STATUS_ACTIVE]
        );
    }

    private function pendingBonus(Request $request): float
    {
        $user = $request->user();

        return (float) DepositRequest::query()
            ->where('user_id', $user->id)
            ->whereNotNull('promotion_id')
            ->where('status', DepositRequest::STATUS_APPROVED)
            ->where('bonus_status', 'in_progress')
            ->sum('bonus_amount');
    }

    public function chips(Request $request)
    {
        $user = $request->user();
        $wallet = $this->getWallet($request, Wallet::TYPE_CHIPS);
        $chips = $this->blcNum((string) $wallet->balance);

        return response()
            ->json([
                'ok' => true,
                'currency' => $user->currency ?? 'MYR',
                'balance' => $chips,
                'chips' => $chips,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function main(Request $request)
    {
        $user = $request->user();
        $wallet = $this->getWallet($request, Wallet::TYPE_MAIN);
        $main = $this->blcNum((string) $wallet->balance);

        return response()
            ->json([
                'ok' => true,
                'currency' => $user->currency ?? 'MYR',
                'balance' => $main,
                'main' => $main,
                'cash' => $main,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function all(Request $request)
    {
        $user = $request->user();

        $wMain  = $this->getWallet($request, Wallet::TYPE_MAIN);
        $wChips = $this->getWallet($request, Wallet::TYPE_CHIPS);

        $main  = $this->blcNum((string) $wMain->balance);
        $chips = $this->blcNum((string) $wChips->balance);

        // ✅ bonus from pending promo (NOT wallet bonus)
        $bonus = $this->pendingBonus($request);

        return response()
            ->json([
                'ok' => true,
                'currency' => $user->currency ?? 'MYR',
                'main' => $main,
                'chips' => $chips,
                'bonus' => $bonus,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}