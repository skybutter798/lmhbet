<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DBOXProvider;
use App\Models\DBOXGameCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminDBOXProviderController extends Controller
{
    public function index(Request $request)
    {
        $providers = $this->buildQuery($request)
            ->paginate(25)
            ->withQueryString();

        $stats = $this->stats(clone $this->buildQuery($request, false));

        if ($request->ajax()) {
            return $this->jsonList($providers, $stats);
        }

        return view('admins.dbox.providers.index', [
            'providers' => $providers,
            'stats' => $stats,
            'filters' => $this->filters($request),
        ]);
    }

    public function search(Request $request)
    {
        $providers = $this->buildQuery($request)
            ->paginate(25)
            ->withQueryString();

        $stats = $this->stats(clone $this->buildQuery($request, false));

        return $this->jsonList($providers, $stats);
    }

    public function modal(DBOXProvider $provider)
    {
        $provider->loadCount([
            'games',
            'games as active_games_count' => fn($q) => $q->where('is_active', true),
        ])->load(['primaryImage', 'images']);

        $html = view('admins.dbox.providers.partials.modal', [
            'provider' => $provider,
        ])->render();

        return response()->json(['html' => $html]);
    }

    public function update(Request $request, DBOXProvider $provider)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:80'],
            'is_active' => ['nullable', 'in:0,1'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);

        // If you want code immutable, comment out next line.
        // $provider->code = $data['code'] ?? $provider->code;

        if (array_key_exists('name', $data)) {
            $provider->name = $data['name'] ?? $provider->name;
        }
        if (array_key_exists('code', $data) && $data['code'] !== null) {
            $provider->code = $data['code'];
        }
        if (array_key_exists('is_active', $data)) {
            $provider->is_active = ($data['is_active'] ?? '0') === '1';
        }
        if (array_key_exists('sort_order', $data) && $data['sort_order'] !== null) {
            $provider->sort_order = (int) $data['sort_order'];
        }

        $provider->save();

        $this->clearGameCaches();

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'message' => 'Saved.']);
        }

        return back()->with('success', 'Saved.');
    }

    public function toggleActive(Request $request, DBOXProvider $provider)
    {
        $provider->is_active = !$provider->is_active;
        $provider->save();

        $this->clearGameCaches();

        return response()->json([
            'ok' => true,
            'is_active' => (bool) $provider->is_active,
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
                DBOXProvider::where('id', (int) $row['id'])
                    ->update(['sort_order' => (int) $row['sort_order']]);
            }
        });

        $this->clearGameCaches();

        return response()->json(['ok' => true]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $q = $this->buildQuery($request, false)->get();

        $filename = 'dbox_providers_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'id','code','name','is_active','sort_order','games_count','last_synced_at','created_at','updated_at'
            ]);

            foreach ($q as $p) {
                fputcsv($out, [
                    $p->id,
                    $p->code,
                    $p->name,
                    (int) $p->is_active,
                    (int) ($p->sort_order ?? 0),
                    (int) ($p->games_count ?? 0),
                    $p->last_synced_at,
                    $p->created_at,
                    $p->updated_at,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function jsonList($providers, array $stats)
    {
        $html = view('admins.dbox.providers.partials.table', [
            'providers' => $providers,
        ])->render();

        $pagination = $providers->links('vendor.pagination.admin')->render();

        return response()->json([
            'html' => $html,
            'pagination' => $pagination,
            'total' => $providers->total(),
            'stats' => $stats,
        ]);
    }

    private function filters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'active' => trim((string) $request->query('active', 'all')), // all|1|0
            'sort' => trim((string) $request->query('sort', 'manual')),  // manual|az|za|synced
        ];
    }

    private function buildQuery(Request $request, bool $withCounts = true)
    {
        $f = $this->filters($request);

        $query = DBOXProvider::query();

        if ($withCounts) {
            $query->withCount('games')
                ->withCount(['games as active_games_count' => fn($q) => $q->where('is_active', true)])
                ->with('primaryImage');
        }

        if ($f['q'] !== '') {
            $query->where(function ($qq) use ($f) {
                $qq->where('code', 'like', '%' . $f['q'] . '%')
                   ->orWhere('name', 'like', '%' . $f['q'] . '%');
            });
        }

        if ($f['active'] === '1') $query->where('is_active', true);
        if ($f['active'] === '0') $query->where('is_active', false);

        // Sorting
        if ($f['sort'] === 'az') {
            $query->orderBy('name', 'asc');
        } elseif ($f['sort'] === 'za') {
            $query->orderBy('name', 'desc');
        } elseif ($f['sort'] === 'synced') {
            $query->orderByDesc('last_synced_at');
        } else {
            // manual default
            $query->ordered();
        }

        return $query;
    }

    private function stats($query): array
    {
        $total = (clone $query)->count();
        $active = (clone $query)->where('is_active', true)->count();

        return [
            'total' => $total,
            'active' => $active,
        ];
    }

    /**
     * Your public GamesController caches providers per currency:
     * Cache::remember("games.providers.$currency.v2", 600, ...)
     * This clears those caches so admin changes reflect fast.
     */
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
