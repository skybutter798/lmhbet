<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DBOXGame;
use App\Models\DBOXProvider;
use App\Models\DBOXGameCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDBOXGameSortController extends Controller
{
    private const PAGE_SIZE = 200;

    public function index(Request $request)
    {
        $providers = DBOXProvider::ordered()->get();
        $providerId = (int) ($request->query('provider_id') ?: ($providers->first()?->id ?? 0));

        return view('admins.dbox.games.sort', [
            'providers' => $providers,
            'providerId' => $providerId,
        ]);
    }

    public function list(Request $request)
    {
        $data = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:dbox_providers,id'],
            'q' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:50', 'max:500'],
        ]);

        $providerId = (int) $data['provider_id'];
        $q = trim((string)($data['q'] ?? ''));
        $limit = (int)($data['limit'] ?? self::PAGE_SIZE);

        $query = DBOXGame::query()
            ->where('provider_id', $providerId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc');

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', '%' . $q . '%')
                   ->orWhere('code', 'like', '%' . $q . '%');
            });
        }

        $games = $query->paginate($limit)->withQueryString();

        $html = view('admins.dbox.games.partials.sort_list', [
            'games' => $games,
        ])->render();

        return response()->json([
            'html' => $html,
            'total' => $games->total(),
            'page' => $games->currentPage(),
            'last_page' => $games->lastPage(),
        ]);
    }

    public function autocomplete(Request $request)
    {
        $data = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:dbox_providers,id'],
            'q' => ['required', 'string', 'min:1', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $providerId = (int) $data['provider_id'];
        $q = trim((string) $data['q']);
        $limit = (int)($data['limit'] ?? 20);

        $rows = DBOXGame::query()
            ->where('provider_id', $providerId)
            ->where(function ($qq) use ($q) {
                $qq->where('name', 'like', '%' . $q . '%')
                   ->orWhere('code', 'like', '%' . $q . '%');
            })
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get(['id', 'code', 'name', 'sort_order', 'is_active', 'supports_launch']);

        return response()->json([
            'items' => $rows->map(fn($g) => [
                'id' => $g->id,
                'code' => $g->code,
                'name' => $g->name,
                'sort_order' => (int)($g->sort_order ?? 0),
                'is_active' => (bool)$g->is_active,
                'hot' => (bool)$g->supports_launch,
            ])->all()
        ]);
    }

    /**
     * Simple dense ordering:
     * - All games are 0..N-1 (unique) for each provider
     * - Move operations SHIFT other rows to keep uniqueness
     */
    public function move(Request $request)
    {
        $data = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:dbox_providers,id'],
            'game_id' => ['required', 'integer', 'exists:dbox_games,id'],
            'mode' => ['required', 'in:top,bottom,before,after,nudge_up,nudge_down'],
            'ref_id' => ['nullable', 'integer', 'exists:dbox_games,id'],
        ]);

        $providerId = (int) $data['provider_id'];
        $gameId = (int) $data['game_id'];
        $mode = (string) $data['mode'];
        $refId = isset($data['ref_id']) ? (int)$data['ref_id'] : null;

        try {
            DB::transaction(function () use ($providerId, $gameId, $mode, $refId) {

                // ✅ Ensure provider ordering is clean 0..N-1 and unique
                $this->normalizeIfNeededLocked($providerId);

                /** @var DBOXGame $game */
                $game = DBOXGame::query()
                    ->where('id', $gameId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int)$game->provider_id !== $providerId) {
                    throw new \RuntimeException('Game does not belong to this provider.');
                }

                $cur = (int)($game->sort_order ?? 0);

                $max = (int)(DBOXGame::query()
                    ->where('provider_id', $providerId)
                    ->lockForUpdate()
                    ->max('sort_order') ?? 0);

                if ($mode === 'top') {
                    if ($cur === 0) return;

                    // shift 0..cur-1 down (+1)
                    DBOXGame::query()
                        ->where('provider_id', $providerId)
                        ->where('id', '!=', $game->id)
                        ->where('sort_order', '<', $cur)
                        ->increment('sort_order', 1);

                    $game->sort_order = 0;
                    $game->save();
                    return;
                }

                if ($mode === 'bottom') {
                    if ($cur === $max) return;

                    // shift cur+1..max up (-1)
                    DBOXGame::query()
                        ->where('provider_id', $providerId)
                        ->where('id', '!=', $game->id)
                        ->where('sort_order', '>', $cur)
                        ->decrement('sort_order', 1);

                    $game->sort_order = $max;
                    $game->save();
                    return;
                }

                if ($mode === 'nudge_up') {
                    if ($cur === 0) return;
                    $this->swapWithSort($providerId, $game->id, $cur, $cur - 1);
                    return;
                }

                if ($mode === 'nudge_down') {
                    if ($cur === $max) return;
                    $this->swapWithSort($providerId, $game->id, $cur, $cur + 1);
                    return;
                }

                // before/after need reference
                if (!$refId || $refId === $gameId) {
                    throw new \RuntimeException('Reference game is required and must be different.');
                }

                /** @var DBOXGame $ref */
                $ref = DBOXGame::query()
                    ->where('id', $refId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int)$ref->provider_id !== $providerId) {
                    throw new \RuntimeException('Reference game must be in same provider.');
                }

                $refSort = (int)($ref->sort_order ?? 0);

                if ($mode === 'before') {
                    $this->moveBefore($providerId, $game, $cur, $refSort);
                    return;
                }

                if ($mode === 'after') {
                    $this->moveAfter($providerId, $game, $cur, $refSort);
                    return;
                }
            });
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Move failed: ' . $e->getMessage(),
            ], 422);
        }

        $this->clearGameCaches();

        return response()->json(['ok' => true, 'message' => 'Moved.']);
    }

    /**
     * Normalize provider to 0..N-1 (unique) in current order.
     * Use this to fix your current 1000/20000 values back into 0/1/2...
     */
    public function renumber(Request $request)
    {
        $data = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:dbox_providers,id'],
        ]);

        $providerId = (int)$data['provider_id'];

        DB::transaction(function () use ($providerId) {
            $this->normalizeProviderLocked($providerId);
        });

        $this->clearGameCaches();

        return response()->json(['ok' => true, 'message' => 'Normalized to 0..N-1.']);
    }

    // ----------------------------
    // Helpers
    // ----------------------------

    private function normalizeIfNeededLocked(int $providerId): void
    {
        $stats = DBOXGame::query()
            ->where('provider_id', $providerId)
            ->lockForUpdate()
            ->selectRaw('COUNT(*) as cnt, COUNT(DISTINCT sort_order) as dcnt, MIN(sort_order) as min_so, MAX(sort_order) as max_so')
            ->first();

        $cnt = (int)($stats->cnt ?? 0);
        if ($cnt <= 1) return;

        $dcnt = (int)($stats->dcnt ?? 0);
        $min = (int)($stats->min_so ?? 0);
        $max = (int)($stats->max_so ?? 0);

        // We want: min=0, max=cnt-1, distinct=cnt
        if ($min !== 0 || $max !== ($cnt - 1) || $dcnt !== $cnt) {
            $this->normalizeProviderLocked($providerId);
        }
    }

    private function normalizeProviderLocked(int $providerId): void
    {
        $ids = DBOXGame::query()
            ->where('provider_id', $providerId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->pluck('id')
            ->all();

        foreach ($ids as $i => $id) {
            DBOXGame::where('id', $id)->update(['sort_order' => $i]);
        }
    }

    private function swapWithSort(int $providerId, int $gameId, int $curSort, int $otherSort): void
    {
        // Find neighbor by sort_order
        $other = DBOXGame::query()
            ->where('provider_id', $providerId)
            ->where('sort_order', $otherSort)
            ->lockForUpdate()
            ->first();

        if (!$other) return;

        // swap
        DBOXGame::where('id', $other->id)->update(['sort_order' => $curSort]);
        DBOXGame::where('id', $gameId)->update(['sort_order' => $otherSort]);
    }

    /**
     * Move game immediately BEFORE reference.
     * This shifts rows so sort_order stays unique.
     */
    private function moveBefore(int $providerId, DBOXGame $game, int $cur, int $refSort): void
    {
        if ($cur === $refSort) return;

        if ($cur < $refSort) {
            // Moving downwards to before ref:
            // items (cur+1 .. refSort-1) shift up (-1)
            $from = $cur + 1;
            $to = $refSort - 1;
            if ($from <= $to) {
                DBOXGame::query()
                    ->where('provider_id', $providerId)
                    ->where('id', '!=', $game->id)
                    ->whereBetween('sort_order', [$from, $to])
                    ->decrement('sort_order', 1);
            }
            $game->sort_order = $refSort - 1;
            $game->save();
            return;
        }

        // cur > refSort: moving up to refSort
        // items (refSort .. cur-1) shift down (+1)
        $from = $refSort;
        $to = $cur - 1;
        if ($from <= $to) {
            DBOXGame::query()
                ->where('provider_id', $providerId)
                ->where('id', '!=', $game->id)
                ->whereBetween('sort_order', [$from, $to])
                ->increment('sort_order', 1);
        }
        $game->sort_order = $refSort;
        $game->save();
    }

    /**
     * Move game immediately AFTER reference.
     * This shifts rows so sort_order stays unique.
     */
    private function moveAfter(int $providerId, DBOXGame $game, int $cur, int $refSort): void
    {
        if ($cur === $refSort) return;

        if ($cur < $refSort) {
            // Moving downwards to after ref:
            // items (cur+1 .. refSort) shift up (-1)
            $from = $cur + 1;
            $to = $refSort;
            if ($from <= $to) {
                DBOXGame::query()
                    ->where('provider_id', $providerId)
                    ->where('id', '!=', $game->id)
                    ->whereBetween('sort_order', [$from, $to])
                    ->decrement('sort_order', 1);
            }
            $game->sort_order = $refSort;
            $game->save();
            return;
        }

        // cur > refSort: moving up to after ref (refSort+1)
        // items (refSort+1 .. cur-1) shift down (+1)
        $from = $refSort + 1;
        $to = $cur - 1;
        if ($from <= $to) {
            DBOXGame::query()
                ->where('provider_id', $providerId)
                ->where('id', '!=', $game->id)
                ->whereBetween('sort_order', [$from, $to])
                ->increment('sort_order', 1);
        }
        $game->sort_order = $refSort + 1;
        $game->save();
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
            // ignore
        }
    }
    
    public function reorder(Request $request)
    {
        $data = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:dbox_providers,id'],
            'ordered_ids' => ['required', 'array', 'min:2'],
            'ordered_ids.*' => ['integer', 'exists:dbox_games,id'],
            // optional: 'page' / 'limit' if you want to validate page scope
        ]);
    
        $providerId = (int) $data['provider_id'];
        $orderedIds = array_values(array_map('intval', $data['ordered_ids']));
    
        try {
            DB::transaction(function () use ($providerId, $orderedIds) {
                // lock provider rows
                $rows = DBOXGame::query()
                    ->where('provider_id', $providerId)
                    ->lockForUpdate()
                    ->get(['id', 'sort_order']);
    
                if ($rows->isEmpty()) return;
    
                $idSet = array_flip($rows->pluck('id')->map(fn($v)=>(int)$v)->all());
    
                // ensure all provided IDs belong to provider
                foreach ($orderedIds as $id) {
                    if (!isset($idSet[$id])) {
                        throw new \RuntimeException("Game #$id not in provider.");
                    }
                }
    
                // ✅ Ensure clean dense ordering first (important for paging consistency)
                $this->normalizeProviderLocked($providerId);
    
                // Now re-apply by moving each dragged row into place among the subset
                // Approach: assign temporary high values, then re-pack to 0..N-1.
    
                // Get current list in order
                $allIds = DBOXGame::query()
                    ->where('provider_id', $providerId)
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->pluck('id')
                    ->map(fn($v)=>(int)$v)
                    ->all();
    
                // Rebuild sequence:
                // - take allIds
                // - for ids that are in orderedIds (this page), replace them with orderedIds in that relative segment
                //
                // This keeps other pages stable, and only rearranges the rows you dragged.
                $orderedSet = array_flip($orderedIds);
    
                $newAll = [];
                $buffer = [];
    
                foreach ($allIds as $id) {
                    if (isset($orderedSet[$id])) {
                        $buffer[] = $id;
                    } else {
                        if ($buffer) {
                            // flush dragged subset in the user’s new order (only once per contiguous block)
                            // IMPORTANT: This assumes the page is a contiguous block in sort_order,
                            // which it is with pagination orderBy sort_order asc.
                            $newAll = array_merge($newAll, $orderedIds);
                            $buffer = [];
                            // clear orderedIds so we don't insert again
                            $orderedIds = [];
                            $orderedSet = [];
                        }
                        $newAll[] = $id;
                    }
                }
                if ($buffer) {
                    $newAll = array_merge($newAll, $orderedIds);
                }
    
                // final renumber 0..N-1
                foreach ($newAll as $i => $id) {
                    DBOXGame::where('id', $id)->update(['sort_order' => $i]);
                }
            });
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Reorder failed: ' . $e->getMessage(),
            ], 422);
        }
    
        $this->clearGameCaches();
    
        return response()->json(['ok' => true, 'message' => 'Reordered.']);
    }

}
