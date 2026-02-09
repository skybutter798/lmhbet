<?php
// /home/lmh/app/Http/Controllers/Admin/AdminDashboardController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $admin = Auth::guard('admin')->user();

        $start = now()->copy()->startOfDay();
        $end   = now()->copy()->endOfDay();

        // -----------------------
        // Users
        // -----------------------
        $usersTotal = (int) DB::table('users')->count();

        $usersToday = (int) DB::table('users')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $latestUsers = DB::table('users')
            ->orderByDesc('id')
            ->limit(8)
            ->get(['id','username','email','country','currency','created_at']);

        // -----------------------
        // KYC
        // -----------------------
        $kycPending = (int) DB::table('kyc_submissions')
            ->where('status', 'pending')
            ->count();

        $kycApprovedToday = (int) DB::table('kyc_submissions')
            ->where('status', 'approved')
            ->whereBetween('verified_at', [$start, $end])
            ->count();

        // -----------------------
        // Bets (today)
        // -----------------------
        $betStatsToday = DB::table('bet_records as br')
            ->whereBetween('br.bet_at', [$start, $end])
            ->selectRaw('COUNT(*) as bet_count')
            ->selectRaw('CAST(COALESCE(SUM(br.stake_amount),0) AS DECIMAL(36,2)) as stake_sum')
            ->selectRaw('CAST(COALESCE(SUM(br.payout_amount),0) AS DECIMAL(36,2)) as payout_sum')
            ->selectRaw('CAST(COALESCE(SUM(br.profit_amount),0) AS DECIMAL(36,2)) as profit_sum')
            ->first();

        $betsToday   = (int)($betStatsToday->bet_count ?? 0);
        $stakeToday  = (string)($betStatsToday->stake_sum ?? '0.00');
        $payoutToday = (string)($betStatsToday->payout_sum ?? '0.00');
        $profitToday = (string)($betStatsToday->profit_sum ?? '0.00');

        // -----------------------
        // Bets (all-time)
        // -----------------------
        $betStatsAll = DB::table('bet_records as br')
            ->selectRaw('COUNT(*) as bet_count')
            ->selectRaw('CAST(COALESCE(SUM(br.stake_amount),0) AS DECIMAL(36,2)) as stake_sum')
            ->selectRaw('CAST(COALESCE(SUM(br.payout_amount),0) AS DECIMAL(36,2)) as payout_sum')
            ->selectRaw('CAST(COALESCE(SUM(br.profit_amount),0) AS DECIMAL(36,2)) as profit_sum')
            ->first();

        $betsAll   = (int)($betStatsAll->bet_count ?? 0);
        $stakeAll  = (string)($betStatsAll->stake_sum ?? '0.00');
        $payoutAll = (string)($betStatsAll->payout_sum ?? '0.00');
        $profitAll = (string)($betStatsAll->profit_sum ?? '0.00');

        // -----------------------
        // Top games (today)
        // -----------------------
        $topGamesToday = DB::table('bet_records as br')
            ->leftJoin('dbox_providers as p', 'p.code', '=', 'br.provider')
            ->leftJoin('dbox_games as g', function ($j) {
                $j->on('g.code', '=', 'br.game_code');
                $j->on('g.provider_id', '=', 'p.id');
            })
            ->whereBetween('br.bet_at', [$start, $end])
            ->groupBy('br.game_code', 'br.provider', 'g.name', 'p.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(6)
            ->get([
                'br.game_code',
                'br.provider',
                DB::raw('COALESCE(g.name, "") as game_name'),
                DB::raw('COALESCE(p.name, "") as provider_name'),
                DB::raw('COUNT(*) as cnt'),
                DB::raw('CAST(COALESCE(SUM(br.stake_amount),0) AS DECIMAL(36,2)) as stake_sum'),
            ]);

        $topGameToday = $topGamesToday->first();

        // -----------------------
        // Top games (all-time)
        // -----------------------
        $topGamesAll = DB::table('bet_records as br')
            ->leftJoin('dbox_providers as p', 'p.code', '=', 'br.provider')
            ->leftJoin('dbox_games as g', function ($j) {
                $j->on('g.code', '=', 'br.game_code');
                $j->on('g.provider_id', '=', 'p.id');
            })
            ->groupBy('br.game_code', 'br.provider', 'g.name', 'p.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(6)
            ->get([
                'br.game_code',
                'br.provider',
                DB::raw('COALESCE(g.name, "") as game_name'),
                DB::raw('COALESCE(p.name, "") as provider_name'),
                DB::raw('COUNT(*) as cnt'),
                DB::raw('CAST(COALESCE(SUM(br.stake_amount),0) AS DECIMAL(36,2)) as stake_sum'),
            ]);

        $topGameAll = $topGamesAll->first();

        // -----------------------
        // Recent bets (latest)
        // -----------------------
        $recentBets = DB::table('bet_records as br')
            ->leftJoin('users as u', 'u.id', '=', 'br.user_id')
            ->leftJoin('dbox_providers as p', 'p.code', '=', 'br.provider')
            ->leftJoin('dbox_games as g', function ($j) {
                $j->on('g.code', '=', 'br.game_code');
                $j->on('g.provider_id', '=', 'p.id');
            })
            ->orderByDesc('br.id')
            ->limit(8)
            ->get([
                'br.id',
                'br.bet_id',
                'br.round_ref',
                'br.bet_reference',
                'br.bet_at',
                'br.status',
                'br.currency',
                'br.wallet_type',
                DB::raw('CAST(br.stake_amount AS DECIMAL(36,2)) as stake2'),
                DB::raw('CAST(br.payout_amount AS DECIMAL(36,2)) as payout2'),
                DB::raw('CAST(br.profit_amount AS DECIMAL(36,2)) as profit2'),
                'u.id as user_id',
                'u.username',
                DB::raw('COALESCE(p.name,"") as provider_name'),
                DB::raw('COALESCE(g.name,"") as game_name'),
                'br.game_code',
                'br.provider',
            ]);

        // -----------------------
        // Wallet (today)
        // -----------------------
        $walletStatsToday = DB::table('wallet_transactions as wt')
            ->whereBetween('wt.occurred_at', [$start, $end])
            ->selectRaw("CAST(COALESCE(SUM(CASE WHEN wt.direction='credit' THEN wt.amount ELSE 0 END),0) AS DECIMAL(36,2)) as credit_sum")
            ->selectRaw("CAST(COALESCE(SUM(CASE WHEN wt.direction='debit'  THEN wt.amount ELSE 0 END),0) AS DECIMAL(36,2)) as debit_sum")
            ->selectRaw("COUNT(*) as tx_count")
            ->first();

        $walletCreditToday = (string)($walletStatsToday->credit_sum ?? '0.00');
        $walletDebitToday  = (string)($walletStatsToday->debit_sum ?? '0.00');
        $walletTxCntToday  = (int)($walletStatsToday->tx_count ?? 0);

        $walletNetToday = (string) (DB::selectOne("
            SELECT CAST((? - ?) AS DECIMAL(36,2)) as net
        ", [$walletCreditToday, $walletDebitToday])->net ?? '0.00');

        // -----------------------
        // Wallet (all-time)
        // -----------------------
        $walletStatsAll = DB::table('wallet_transactions as wt')
            ->selectRaw("CAST(COALESCE(SUM(CASE WHEN wt.direction='credit' THEN wt.amount ELSE 0 END),0) AS DECIMAL(36,2)) as credit_sum")
            ->selectRaw("CAST(COALESCE(SUM(CASE WHEN wt.direction='debit'  THEN wt.amount ELSE 0 END),0) AS DECIMAL(36,2)) as debit_sum")
            ->selectRaw("COUNT(*) as tx_count")
            ->first();

        $walletCreditAll = (string)($walletStatsAll->credit_sum ?? '0.00');
        $walletDebitAll  = (string)($walletStatsAll->debit_sum ?? '0.00');
        $walletTxCntAll  = (int)($walletStatsAll->tx_count ?? 0);

        $walletNetAll = (string) (DB::selectOne("
            SELECT CAST((? - ?) AS DECIMAL(36,2)) as net
        ", [$walletCreditAll, $walletDebitAll])->net ?? '0.00');

        // -----------------------
        // Scope stats for toggle
        // -----------------------
        $statsToday = [
            'bets'       => $betsToday,
            'stake'      => $stakeToday,
            'profit'     => $profitToday,
            'wallet_net' => $walletNetToday,
            'wallet_tx'  => $walletTxCntToday,

            'top_game_label' => $topGameToday
                ? trim(($topGameToday->game_name ?: $topGameToday->game_code) . ' • ' . ($topGameToday->provider_name ?: $topGameToday->provider))
                : '—',
            'top_game_count'     => $topGameToday ? (int)$topGameToday->cnt : 0,
            'top_game_game_code' => $topGameToday ? (string)$topGameToday->game_code : '',
            'top_game_provider'  => $topGameToday ? (string)$topGameToday->provider  : '',
        ];

        $statsAll = [
            'bets'       => $betsAll,
            'stake'      => $stakeAll,
            'profit'     => $profitAll,
            'wallet_net' => $walletNetAll,
            'wallet_tx'  => $walletTxCntAll,

            'top_game_label' => $topGameAll
                ? trim(($topGameAll->game_name ?: $topGameAll->game_code) . ' • ' . ($topGameAll->provider_name ?: $topGameAll->provider))
                : '—',
            'top_game_count'     => $topGameAll ? (int)$topGameAll->cnt : 0,
            'top_game_game_code' => $topGameAll ? (string)$topGameAll->game_code : '',
            'top_game_provider'  => $topGameAll ? (string)$topGameAll->provider  : '',
        ];

        // -----------------------
        // Other dashboard stats
        // -----------------------
        $stats = [
            'users_total' => $usersTotal,
            'users_today' => $usersToday,
            'kyc_pending' => $kycPending,
            'kyc_approved_today' => $kycApprovedToday,
        ];

        return view('admins.dashboard', compact(
            'admin',
            'stats',
            'statsToday',
            'statsAll',
            'latestUsers',
            'topGamesToday',
            'recentBets'
        ));
    }
}
