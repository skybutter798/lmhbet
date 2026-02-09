<?php

namespace App\Http\Controllers\History;

use App\Http\Controllers\Controller;
use App\Models\BetRecord;
use App\Models\DBOXGame;
use App\Models\DBOXProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GameHistoryController extends Controller
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

        $providers = Cache::remember("games.providers", 600, function () {
            return DBOXProvider::where('is_active', true)
                ->orderBy('name')
                ->get();
        });

        $providerCode = trim((string)$request->query('provider', ''));

        [$start, $end, $range] = $this->rangeToDates($request);

        $q = BetRecord::query()
            ->where('user_id', $user->id)
            ->whereBetween('bet_at', [$start, $end])
            ->orderByDesc('bet_at');

        if ($providerCode !== '') {
            $q->where('provider', $providerCode);
        }

        $records = $q->paginate(20)->withQueryString();

        // map game_code -> name for current page
        $codes = $records->getCollection()
            ->pluck('game_code')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $gameMap = [];
        if (!empty($codes)) {
            $gameMap = DBOXGame::whereIn('code', $codes)
                ->pluck('name', 'code')
                ->toArray();
        }

        $providerMap = $providers->pluck('name', 'code')->toArray();

        return view('history.game', [
            'title' => 'Game History',
            'active' => 'history',
            'activeSub' => 'games',

            'currency' => $user->currency ?? 'MYR',
            'cash' => $cash,
            'chips' => $chips,
            'bonus' => $bonus,

            'providers' => $providers,
            'providerCode' => $providerCode,

            'records' => $records,
            'gameMap' => $gameMap,
            'providerMap' => $providerMap,

            'range' => $range,
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ]);
    }
}
