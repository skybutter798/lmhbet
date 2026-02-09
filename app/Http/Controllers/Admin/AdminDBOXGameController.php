<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DBOXGame;
use App\Models\DBOXProvider;
use App\Models\DBOXGameCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminDBOXGameController extends Controller
{
    public function index(Request $request)
    {
        $providers = DBOXProvider::ordered()->get();

        $games = $this->buildQuery($request)
            ->paginate(25)
            ->withQueryString();

        $stats = $this->stats(clone $this->buildQuery($request, false));
        $currencies = $this->allCurrencies();

        if ($request->ajax()) {
            return $this->jsonList($games, $stats);
        }

        return view('admins.dbox.games.index', [
            'providers' => $providers,
            'games' => $games,
            'stats' => $stats,
            'filters' => $this->filters($request),
            'currencies' => $currencies,
        ]);
    }

    public function search(Request $request)
    {
        $games = $this->buildQuery($request)
            ->paginate(25)
            ->withQueryString();

        $stats = $this->stats(clone $this->buildQuery($request, false));

        return $this->jsonList($games, $stats);
    }

    public function modal(DBOXGame $game)
    {
        $game->load([
            'provider',
            'primaryImage',
            'images',
            'currencies',
        ]);

        $html = view('admins.dbox.games.partials.modal', [
            'game' => $game,
            'providers' => DBOXProvider::ordered()->get(),
            'allCurrencies' => $this->allCurrencies(),
        ])->render();

        return response()->json(['html' => $html]);
    }

    public function update(Request $request, DBOXGame $game)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:120'],
            'provider_id' => ['nullable', 'integer', 'exists:dbox_providers,id'],
            'supports_launch' => ['nullable', 'in:0,1'],
            'is_active' => ['nullable', 'in:0,1'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'product_group' => ['nullable', 'string', 'max:120'],
            'sub_product_group' => ['nullable', 'string', 'max:120'],
            'product_group_name' => ['nullable', 'string', 'max:255'],
            'sub_product_group_name' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('name', $data) && $data['name'] !== null) $game->name = $data['name'];
        if (array_key_exists('code', $data) && $data['code'] !== null) $game->code = $data['code'];
        if (array_key_exists('provider_id', $data) && $data['provider_id'] !== null) $game->provider_id = (int) $data['provider_id'];

        if (array_key_exists('supports_launch', $data)) $game->supports_launch = ($data['supports_launch'] ?? '0') === '1';
        if (array_key_exists('is_active', $data)) $game->is_active = ($data['is_active'] ?? '0') === '1';
        if (array_key_exists('sort_order', $data) && $data['sort_order'] !== null) $game->sort_order = (int) $data['sort_order'];

        foreach (['product_group','sub_product_group','product_group_name','sub_product_group_name'] as $k) {
            if (array_key_exists($k, $data)) {
                $game->{$k} = $data[$k];
            }
        }

        $game->save();

        $this->clearGameCaches();

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'message' => 'Saved.']);
        }

        return back()->with('success', 'Saved.');
    }

    public function toggleActive(Request $request, DBOXGame $game)
    {
        $game->is_active = !$game->is_active;
        $game->save();

        $this->clearGameCaches();

        return response()->json([
            'ok' => true,
            'is_active' => (bool) $game->is_active,
        ]);
    }

    public function bulkSort(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $row) {
                DBOXGame::where('id', (int) $row['id'])
                    ->update(['sort_order' => (int) $row['sort_order']]);
            }
        });

        $this->clearGameCaches();

        return response()->json(['ok' => true]);
    }

    public function updateCurrencies(Request $request, DBOXGame $game)
    {
        $data = $request->validate([
            'active_currencies' => ['nullable', 'array'],
            'active_currencies.*' => ['string', 'max:10'],
        ]);

        $active = collect($data['active_currencies'] ?? [])->map(fn($x) => strtoupper(trim($x)))->unique()->values();

        // Ensure rows exist for each currency known, but keep it safe: only touch currencies in the UI.
        $all = $this->allCurrencies();

        DB::transaction(function () use ($game, $active, $all) {
            foreach ($all as $cur) {
                $row = DBOXGameCurrency::firstOrNew([
                    'game_id' => $game->id,
                    'currency' => $cur,
                ]);

                $row->is_active = $active->contains($cur);
                $row->save();
            }
        });

        $this->clearGameCaches();

        return response()->json(['ok' => true, 'message' => 'Currencies updated.']);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $q = $this->buildQuery($request, false)->with('provider')->get();

        $filename = 'dbox_games_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'id','provider_code','provider_name','code','name','supports_launch','is_active','sort_order',
                'product_group','sub_product_group','last_seen_at','created_at','updated_at'
            ]);

            foreach ($q as $g) {
                fputcsv($out, [
                    $g->id,
                    $g->provider?->code,
                    $g->provider?->name,
                    $g->code,
                    $g->name,
                    (int) $g->supports_launch,
                    (int) $g->is_active,
                    (int) ($g->sort_order ?? 0),
                    $g->product_group,
                    $g->sub_product_group,
                    $g->last_seen_at,
                    $g->created_at,
                    $g->updated_at,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function jsonList($games, array $stats)
    {
        $html = view('admins.dbox.games.partials.table', [
            'games' => $games,
        ])->render();

        $pagination = $games->links('vendor.pagination.admin')->render();

        return response()->json([
            'html' => $html,
            'pagination' => $pagination,
            'total' => $games->total(),
            'stats' => $stats,
        ]);
    }

    private function filters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'provider_id' => trim((string) $request->query('provider_id', '')),
            'currency' => strtoupper(trim((string) $request->query('currency', ''))),
            'active' => trim((string) $request->query('active', 'all')), // all|1|0
            'hot' => trim((string) $request->query('hot', 'all')),       // all|1|0
            'sort' => trim((string) $request->query('sort', 'manual')),  // manual|az|za|last_seen
        ];
    }

    private function buildQuery(Request $request, bool $withCounts = true)
    {
        $f = $this->filters($request);

        $query = DBOXGame::query()
            ->with(['provider', 'primaryImage']);

        if ($withCounts) {
            $query->withCount([
                'currencies as active_currency_count' => fn($q) => $q->where('is_active', true),
            ]);
        }

        if ($f['provider_id'] !== '') {
            $query->where('provider_id', (int) $f['provider_id']);
        }

        if ($f['q'] !== '') {
            $query->where(function ($qq) use ($f) {
                $qq->where('name', 'like', '%' . $f['q'] . '%')
                   ->orWhere('code', 'like', '%' . $f['q'] . '%');
            });
        }

        if ($f['active'] === '1') $query->where('is_active', true);
        if ($f['active'] === '0') $query->where('is_active', false);

        if ($f['hot'] === '1') $query->where('supports_launch', true);
        if ($f['hot'] === '0') $query->where('supports_launch', false);

        if ($f['currency'] !== '') {
            $query->whereHas('currencies', fn($x) =>
                $x->where('currency', $f['currency'])->where('is_active', true)
            );
        }

        // Sorting
        if ($f['sort'] === 'az') {
            $query->orderBy('name', 'asc');
        } elseif ($f['sort'] === 'za') {
            $query->orderBy('name', 'desc');
        } elseif ($f['sort'] === 'last_seen') {
            $query->orderByDesc('last_seen_at');
        } else {
            // manual default (sort_order then name)
            $query->ordered();
        }

        return $query;
    }

    private function stats($query): array
    {
        $total = (clone $query)->count();
        $active = (clone $query)->where('is_active', true)->count();
        $hot = (clone $query)->where('supports_launch', true)->count();

        return [
            'total' => $total,
            'active' => $active,
            'hot' => $hot,
        ];
    }

    private function allCurrencies(): array
    {
        return DBOXGameCurrency::query()
            ->select('currency')
            ->distinct()
            ->orderBy('currency')
            ->pluck('currency')
            ->map(fn($x) => strtoupper(trim((string) $x)))
            ->filter(fn($x) => $x !== '')
            ->values()
            ->all();
    }

    private function clearGameCaches(): void
    {
        try {
            $currencies = DBOXGameCurrency::query()
                ->select('currency')
                ->distinct()
                ->pluck('currency');

            foreach ($currencies as $c) {
                Cache::forget("games.providers.$c.v2");
            }
        } catch (\Throwable $e) {
            // safe no-op
        }
    }
}
