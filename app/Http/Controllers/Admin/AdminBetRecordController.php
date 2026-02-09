<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BetRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminBetRecordController extends Controller
{
    public function index(Request $request)
    {
        $bets = $this->applyFilters($this->baseListQuery(), $request)
            ->orderByDesc('br.id')
            ->paginate(20)
            ->withQueryString();

        $bets->withPath(route('admin.betrecords.search'));

        $stats = $this->getStats($request);

        return view('admins.betrecords.index', compact('bets', 'stats'));
    }

    public function search(Request $request)
    {
        $bets = $this->applyFilters($this->baseListQuery(), $request)
            ->orderByDesc('br.id')
            ->paginate(20)
            ->withQueryString();

        $bets->withPath(route('admin.betrecords.search'));

        $stats = $this->getStats($request);

        return response()->json([
            'html' => view('admins.betrecords.partials.table', compact('bets'))->render(),
            'pagination' => $bets->links('vendor.pagination.admin')->render(),
            'total' => $bets->total(),
            'stats' => [
                'count' => (int)($stats->count ?? 0),
                'stake_sum' => (string)($stats->stake_sum ?? '0.00'),
                'payout_sum' => (string)($stats->payout_sum ?? '0.00'),
                'profit_sum' => (string)($stats->profit_sum ?? '0.00'),
            ],
        ]);
    }

    public function modal(BetRecord $betRecord)
    {
        // fetch joined display fields (provider_name, game_name, username, etc)
        $bet = $this->baseModalQuery()
            ->where('br.id', $betRecord->id)
            ->first();

        if (!$bet) abort(404);

        $meta = null;
        if (!empty($bet->meta)) {
            $decoded = json_decode((string)$bet->meta, true);
            $meta = is_array($decoded) ? $decoded : null;
        }

        return response()->json([
            'html' => view('admins.betrecords.partials.modal', compact('bet', 'meta'))->render(),
        ]);
    }

    public function exportCsv(Request $request)
    {
        $query = $this->applyFilters($this->baseExportQuery(), $request)
            ->orderByDesc('br.id');

        $filename = 'bet_records_export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'id',
                'bet_at',
                'settled_at',
                'status',
                'user_id',
                'username',
                'email',
                'provider',
                'provider_name',
                'game_code',
                'game_name',
                'round_ref',
                'bet_id',
                'currency',
                'wallet_type',
                'stake_amount',
                'payout_amount',
                'profit_amount',
                'bet_reference',
                'settle_reference',
                'meta',
                'created_at',
                'updated_at',
            ]);

            $query->chunkById(1000, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->id,
                        $r->bet_at,
                        $r->settled_at,
                        $r->status,
                        $r->user_id,
                        $r->username,
                        $r->email,
                        $r->provider,
                        $r->provider_name,
                        $r->game_code,
                        $r->game_name,
                        $r->round_ref,
                        $r->bet_id,
                        $r->currency,
                        $r->wallet_type,
                        $r->stake_amount,
                        $r->payout_amount,
                        $r->profit_amount,
                        $r->bet_reference,
                        $r->settle_reference,
                        $r->meta,
                        $r->created_at,
                        $r->updated_at,
                    ]);
                }
            }, 'br.id');

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // =========================
    // Queries
    // =========================

    private function baseJoins()
    {
        return DB::table('bet_records as br')
            ->leftJoin('users as u', 'u.id', '=', 'br.user_id')
            ->leftJoin('dbox_providers as p', 'p.code', '=', 'br.provider')
            ->leftJoin('dbox_games as g', function ($j) {
                $j->on('g.code', '=', 'br.game_code');
                $j->on('g.provider_id', '=', 'p.id');
            });
    }

    private function baseListQuery()
    {
        // list optimized columns + pretty (2dp) numbers
        return $this->baseJoins()->select([
            'br.id',
            'br.user_id',
            'br.provider',
            'br.round_ref',
            'br.bet_id',
            'br.game_code',
            'br.currency',
            'br.wallet_type',
            'br.status',
            'br.bet_reference',
            'br.settle_reference',
            'br.bet_at',
            'br.settled_at',
            'br.created_at',
            'u.username',
            'u.email',
            DB::raw('COALESCE(p.name, "") as provider_name'),
            DB::raw('COALESCE(g.name, "") as game_name'),
            DB::raw('CAST(br.stake_amount AS DECIMAL(36,2)) as stake2'),
            DB::raw('CAST(br.payout_amount AS DECIMAL(36,2)) as payout2'),
            DB::raw('CAST(br.profit_amount AS DECIMAL(36,2)) as profit2'),
        ]);
    }

    private function baseModalQuery()
    {
        // include meta + raw amounts for deep inspection
        return $this->baseJoins()->select([
            'br.*',
            'u.username',
            'u.email',
            'u.country as user_country',
            'u.currency as user_currency',
            DB::raw('COALESCE(p.name, "") as provider_name'),
            DB::raw('COALESCE(g.name, "") as game_name'),
        ]);
    }

    private function baseExportQuery()
    {
        // export includes meta + joined names + raw amounts
        return $this->baseJoins()->select([
            'br.*',
            'u.username',
            'u.email',
            DB::raw('COALESCE(p.name, "") as provider_name'),
            DB::raw('COALESCE(g.name, "") as game_name'),
        ]);
    }

    private function baseStatsQuery()
    {
        // same joins so filters on username/provider/game work
        return $this->baseJoins();
    }

    private function getStats(Request $request)
    {
        $q = $this->applyFilters($this->baseStatsQuery(), $request);

        return $q->selectRaw('COUNT(*) as count')
            ->selectRaw('CAST(COALESCE(SUM(br.stake_amount),0) AS DECIMAL(36,2)) as stake_sum')
            ->selectRaw('CAST(COALESCE(SUM(br.payout_amount),0) AS DECIMAL(36,2)) as payout_sum')
            ->selectRaw('CAST(COALESCE(SUM(br.profit_amount),0) AS DECIMAL(36,2)) as profit_sum')
            ->first();
    }

    // =========================
    // Filters
    // =========================

    private function applyFilters($query, Request $request)
    {
        $q = trim((string)$request->query('q', ''));
        $userId = trim((string)$request->query('user_id', ''));
        $provider = trim((string)$request->query('provider', ''));
        $game = trim((string)$request->query('game', ''));
        $status = $request->query('status', 'all');
        $currency = trim((string)$request->query('currency', ''));
        $walletType = $request->query('wallet_type', 'all');

        $from = trim((string)$request->query('from', ''));               // bet_at from
        $to = trim((string)$request->query('to', ''));                   // bet_at to
        $settledFrom = trim((string)$request->query('settled_from', ''));
        $settledTo = trim((string)$request->query('settled_to', ''));

        $minStake = trim((string)$request->query('min_stake', ''));
        $maxStake = trim((string)$request->query('max_stake', ''));
        $minProfit = trim((string)$request->query('min_profit', ''));
        $maxProfit = trim((string)$request->query('max_profit', ''));

        $onlyUnsettled = $request->query('only_unsettled', '0');
        $onlyProfit = $request->query('only_profit', '0');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('u.username', 'like', "%{$q}%")
                    ->orWhere('u.email', 'like', "%{$q}%")
                    ->orWhere('br.round_ref', 'like', "%{$q}%")
                    ->orWhere('br.bet_id', 'like', "%{$q}%")
                    ->orWhere('br.bet_reference', 'like', "%{$q}%")
                    ->orWhere('br.settle_reference', 'like', "%{$q}%")
                    ->orWhere('br.game_code', 'like', "%{$q}%")
                    ->orWhere('br.provider', 'like', "%{$q}%");
            });
        }

        if ($userId !== '') {
            if (ctype_digit($userId)) {
                $query->where('br.user_id', (int)$userId);
            }
        }

        if ($provider !== '') {
            // allow provider code or provider name
            $query->where(function ($w) use ($provider) {
                $w->where('br.provider', $provider)
                  ->orWhere('p.name', 'like', "%{$provider}%")
                  ->orWhere('p.code', 'like', "%{$provider}%");
            });
        }

        if ($game !== '') {
            // allow game_code or game name
            $query->where(function ($w) use ($game) {
                $w->where('br.game_code', 'like', "%{$game}%")
                  ->orWhere('g.name', 'like', "%{$game}%");
            });
        }

        if ($status !== 'all') {
            $query->where('br.status', $status);
        }

        if ($currency !== '') {
            $query->where('br.currency', $currency);
        }

        if ($walletType !== 'all') {
            $query->where('br.wallet_type', $walletType);
        }

        if ($onlyUnsettled === '1') {
            $query->whereNull('br.settled_at');
        }

        if ($onlyProfit === '1') {
            $query->where('br.profit_amount', '>', 0);
        }

        if ($from !== '') $query->whereDate('br.bet_at', '>=', $from);
        if ($to !== '') $query->whereDate('br.bet_at', '<=', $to);

        if ($settledFrom !== '') $query->whereDate('br.settled_at', '>=', $settledFrom);
        if ($settledTo !== '') $query->whereDate('br.settled_at', '<=', $settledTo);

        if ($minStake !== '' && is_numeric($minStake)) $query->where('br.stake_amount', '>=', $minStake);
        if ($maxStake !== '' && is_numeric($maxStake)) $query->where('br.stake_amount', '<=', $maxStake);

        if ($minProfit !== '' && is_numeric($minProfit)) $query->where('br.profit_amount', '>=', $minProfit);
        if ($maxProfit !== '' && is_numeric($maxProfit)) $query->where('br.profit_amount', '<=', $maxProfit);

        return $query;
    }
}
