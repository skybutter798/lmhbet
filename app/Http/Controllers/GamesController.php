<?php

namespace App\Http\Controllers;

use App\Models\DBOXGame;
use App\Models\DBOXProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GamesController extends Controller
{
    public function index(Request $request)
    {
        $currency = auth()->user()->currency ?? 'MYR';

        $providerCode = trim((string) $request->query('provider', ''));
        $q    = trim((string) $request->query('q', ''));
        $sort = trim((string) $request->query('sort', '')); // az | za | manual | (default)
        $hot  = trim((string) $request->query('hot', ''));  // 1 = hot only

        // ✅ If user didn't choose sort, default to MANUAL (sort_order, then name)
        // You can make this always manual, or only when providerCode is set.
        if ($sort === '') {
            $sort = 'manual';
            // If you only want provider pages manual:
            // $sort = ($providerCode !== '') ? 'manual' : '';
        }

        $providers = Cache::remember("games.providers.$currency.v2", 600, function () use ($currency) {
            return DBOXProvider::where('is_active', 1)
                ->whereHas('games', function ($q) use ($currency) {
                    $q->where('is_active', 1)
                      ->whereHas('currencies', fn ($x) =>
                          $x->where('currency', $currency)->where('is_active', 1)
                      );
                })
                ->with('primaryImage')
                ->orderBy('name')
                ->get();
        });

        $provider = null;
        if ($providerCode !== '') {
            $provider = $providers->firstWhere('code', $providerCode)
                ?: DBOXProvider::where('is_active', true)->where('code', $providerCode)->first();
        }

        $gamesQuery = DBOXGame::query()
            ->where('is_active', true)
            ->whereHas('currencies', fn ($x) =>
                $x->where('currency', $currency)->where('is_active', true)
            )
            ->with(['provider.primaryImage', 'primaryImage']);

        // Provider filter
        if ($provider) {
            $gamesQuery->where('provider_id', $provider->id);
        }

        // Search filter
        if ($q !== '') {
            $gamesQuery->where(function ($qq) use ($q) {
                $qq->where('name', 'like', '%' . $q . '%')
                   ->orWhere('code', 'like', '%' . $q . '%');
            });
        }

        // Hot filter (supports_launch = hot)
        if ($hot === '1') {
            $gamesQuery->where('supports_launch', true);
        }

        // ✅ Sorting
        if ($sort === 'az') {
            $gamesQuery->orderBy('name', 'asc');
        } elseif ($sort === 'za') {
            $gamesQuery->orderBy('name', 'desc');
        } elseif ($sort === 'manual') {
            // sort_order ASC, if same then name ASC
            $gamesQuery->ordered();
        } else {
            // fallback (if you still want)
            $gamesQuery->orderByDesc('last_seen_at');
        }

        $games = $gamesQuery->paginate(72)->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('games._grid', ['games' => $games])->render(),
                'next' => $games->nextPageUrl(),
            ]);
        }

        return view('games.index', [
            'title' => $provider ? ($provider->name . ' Games') : 'Games',
            'currency' => $currency,
            'providers' => $providers,
            'provider' => $provider,
            'providerCode' => $providerCode,
            'games' => $games,
            'q' => $q,
            'sort' => $sort,
            'hot' => $hot,
        ]);
    }

    public function play(Request $request, DBOXGame $game)
    {
        $user = $request->user();

        $wallets = $user->wallets()
            ->whereIn('type', ['chips', 'bonus'])
            ->get()
            ->keyBy('type');

        return view('games.play', [
            'title' => $game->name,
            'game'  => $game,

            'chips' => (float) ($wallets->get('chips')?->balance ?? 0),
            'bonus' => (float) ($wallets->get('bonus')?->balance ?? 0),
            'currency' => $user->currency ?? 'MYR',
        ]);
    }
}
