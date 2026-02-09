<?php

namespace App\Http\Controllers\History;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class TransactionHistoryController extends Controller
{
    private function rangeToDates(Request $request): array
    {
        $range = (string)$request->query('range', 'today');
        $from = $request->query('from');
        $to   = $request->query('to');

        $now = now();

        if ($range === 'custom' && $from && $to) {
            $start = \Carbon\Carbon::parse($from)->startOfDay();
            $end   = \Carbon\Carbon::parse($to)->endOfDay();
            return [$start, $end, 'custom'];
        }

        if ($range === 'yesterday') {
            $start = $now->copy()->subDay()->startOfDay();
            $end   = $now->copy()->subDay()->endOfDay();
            return [$start, $end, 'yesterday'];
        }

        if ($range === 'past7') {
            $start = $now->copy()->subDays(6)->startOfDay();
            $end   = $now->copy()->endOfDay();
            return [$start, $end, 'past7'];
        }

        if ($range === 'past30') {
            $start = $now->copy()->subDays(29)->startOfDay();
            $end   = $now->copy()->endOfDay();
            return [$start, $end, 'past30'];
        }

        if ($range === 'this_month') {
            $start = $now->copy()->startOfMonth()->startOfDay();
            $end   = $now->copy()->endOfDay();
            return [$start, $end, 'this_month'];
        }

        if ($range === 'last_month') {
            $start = $now->copy()->subMonthNoOverflow()->startOfMonth()->startOfDay();
            $end   = $now->copy()->subMonthNoOverflow()->endOfMonth()->endOfDay();
            return [$start, $end, 'last_month'];
        }

        $start = $now->copy()->startOfDay();
        $end   = $now->copy()->endOfDay();
        return [$start, $end, 'today'];
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $wallets = $user->wallets()
            ->whereIn('type', ['main', 'chips', 'bonus'])
            ->get()
            ->keyBy('type');

        $cash  = (float)($wallets->get('main')?->balance ?? 0);
        $chips = (float)($wallets->get('chips')?->balance ?? 0);
        $bonus = (float)($wallets->get('bonus')?->balance ?? 0);

        [$start, $end, $range] = $this->rangeToDates($request);

        $type = (string)$request->query('type', '');

        $q = WalletTransaction::query()
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])

            // ✅ EXCLUDE GAME RECORDS (handled in Game History page)
            // keep it simple: filter by known titles
            ->whereNotIn('title', ['DBOX Bet', 'DBOX Settle'])

            // ❌ REMOVE these, they hide internal transfers if you set provider/round_ref
            // ->whereNull('provider')
            // ->whereNull('round_ref')

            ->orderByDesc('id');

        if ($type !== '') {
            $q->where(function ($qq) use ($type) {
                if ($type === 'deposit') {
                    $qq->where('title', 'like', '%Deposit%')
                       ->orWhere('reference', 'like', 'deposit:%');
                } elseif ($type === 'withdrawal') {
                    $qq->where('title', 'like', '%Withdraw%')
                       ->orWhere('reference', 'like', 'withdraw:%');
                } elseif ($type === 'transfer') {
                    // ✅ make sure internal transfers match here too
                    $qq->where('title', 'like', '%Transfer%')
                       ->orWhere('title', 'like', '%Internal%')
                       ->orWhere('reference', 'like', 'transfer:%')
                       ->orWhere('reference', 'like', 'internal_transfer:%');
                } elseif ($type === 'bonus') {
                    $qq->where('title', 'like', '%Bonus%')
                       ->orWhere('reference', 'like', 'bonus:%');
                } elseif ($type === 'rebate') {
                    $qq->where('title', 'like', '%Rebate%')
                       ->orWhere('reference', 'like', 'rebate:%');
                } elseif ($type === 'referral_rebate') {
                    $qq->where('title', 'like', '%Referral%')
                       ->orWhere('reference', 'like', 'referral:%');
                } elseif ($type === 'adjustment') {
                    $qq->where('title', 'like', '%Adjust%')
                       ->orWhere('reference', 'like', 'adjust:%');
                }
            });
        }

        $txs = $q->paginate(20)->withQueryString();

        return view('history.transaction', [
            'title' => 'Transaction History',
            'active' => 'history',
            'activeSub' => 'transactions',

            'currency' => $user->currency ?? 'MYR',
            'cash' => $cash,
            'chips' => $chips,
            'bonus' => $bonus,

            'txs' => $txs,

            'range' => $range,
            'type' => $type,
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ]);
    }
}
