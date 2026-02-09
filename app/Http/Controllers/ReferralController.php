<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ReferralController extends Controller
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

        $refCount = $user->referrals()->count();

        // Example URL: https://yoursite.com/register?ref=CODE
        $refUrl = url('/register?ref=' . urlencode($user->referral_code ?? ''));

        return view('referral.index', [
            'title' => 'Referral',

            'currency' => $user->currency ?? 'MYR',
            'cash' => $cash,
            'chips' => $chips,
            'bonus' => $bonus,

            'referralCode' => $user->referral_code ?? '',
            'referralUrl' => $refUrl,
            'referralCount' => $refCount,
        ]);
    }
}
