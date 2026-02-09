<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletBalanceController extends Controller
{
    public function chips(Request $request)
    {
        $user = $request->user();

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id, 'type' => Wallet::TYPE_CHIPS],
            ['balance' => '0', 'status' => Wallet::STATUS_ACTIVE]
        );

        // normalize to 2dp (and return as number)
        $chips = (float) number_format((float) $wallet->balance, 2, '.', '');

        return response()
            ->json([
                'ok' => true,
                'currency' => $user->currency ?? 'MYR',
                'chips' => $chips,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
