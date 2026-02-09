<?php

namespace App\Http\Controllers;

use App\Models\DBOXGame;
use App\Models\DBOXProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Parent groups you want to show as tabs.
     * Keys here become the filter keys used in data-filter / data-cat.
     */
    private const GROUPS = [
        'slots' => [
            'label' => 'Slots',
            'children' => ['slot', 'arcade', 'table-game', 'card-game', 'fishing'],
        ],
        'casino' => [
            'label' => 'Casino',
            'children' => ['live-dealer', 'lobby'],
        ],
        'sport' => [
            'label' => 'Sport',
            'children' => ['sportsbook'],
        ],
        'lottery' => [
            'label' => 'Lottery',
            'children' => ['lottery', 'bingo'],
        ],
    ];

    public function index(): View
    {
        $currency = auth()->user()->currency ?? 'MYR';

        $providers = DBOXProvider::where('is_active', true)
            ->with('primaryImage')
            ->ordered()
            ->get();

        // Bump cache version because logic changed
        $productGroups = Cache::remember("home.productGroups.v3.$currency", 600, function () use ($currency) {
            // Get counts by raw product_group_name, then aggregate into parent groups
            $rows = DBOXGame::query()
                ->selectRaw('product_group_name, COUNT(*) as cnt')
                ->where('is_active', true)
                ->whereNotNull('product_group_name')
                ->where('product_group_name', '<>', '')
                ->whereHas('currencies', fn($q) => $q->where('currency', $currency)->where('is_active', true))
                ->groupBy('product_group_name')
                ->get();

            $agg = [
                'slots' => 0,
                'casino' => 0,
                'sport' => 0,
                'lottery' => 0,
            ];

            foreach ($rows as $r) {
                $parentKey = self::toParentKey((string) $r->product_group_name);
                if (!$parentKey) {
                    continue; // ignore unknown groups (still appears in "All" provider list)
                }
                $agg[$parentKey] += (int) $r->cnt;
            }

            // Return in the exact order you want, only groups with > 0
            $out = [];
            foreach (['slots', 'casino', 'sport', 'lottery'] as $k) {
                if (($agg[$k] ?? 0) > 0) {
                    $out[] = [
                        'key' => $k,
                        'label' => self::GROUPS[$k]['label'],
                        'count' => (int) $agg[$k],
                    ];
                }
            }

            return collect($out);
        });

        $providerGroups = Cache::remember("home.providerGroups.v3.$currency", 600, function () use ($currency) {
            $rows = DBOXGame::query()
                ->selectRaw('provider_id, product_group_name, COUNT(*) as cnt')
                ->where('is_active', true)
                ->whereNotNull('product_group_name')
                ->where('product_group_name', '<>', '')
                ->whereHas('currencies', fn($q) => $q->where('currency', $currency)->where('is_active', true))
                ->groupBy('provider_id', 'product_group_name')
                ->get();

            $map = [];

            foreach ($rows as $r) {
                $pid = (int) $r->provider_id;
                $cnt = (int) $r->cnt;

                $parentKey = self::toParentKey((string) $r->product_group_name);
                if (!$parentKey) {
                    continue;
                }

                $map[$pid] ??= ['items' => []];

                $map[$pid]['items'][$parentKey] = [
                    'key' => $parentKey,
                    'label' => self::GROUPS[$parentKey]['label'],
                    'cnt' => ($map[$pid]['items'][$parentKey]['cnt'] ?? 0) + $cnt,
                ];
            }

            $out = [];
            foreach ($map as $pid => $data) {
                $items = array_values($data['items']);
                usort($items, fn($a, $b) => $b['cnt'] <=> $a['cnt']);

                $out[$pid] = [
                    'keys' => array_map(fn($x) => $x['key'], $items),
                    'labels' => array_map(fn($x) => $x['label'], $items),
                ];
            }

            return $out;
        });

        $hotGames = DBOXGame::query()
            ->where('is_active', true)
            ->where('supports_launch', true)
            ->whereHas('currencies', fn($q) => $q->where('currency', $currency)->where('is_active', true))
            ->with(['provider', 'primaryImage'])
            ->ordered()
            ->limit(20)
            ->get();

        $walletBalances = [];

        if (auth()->check()) {
            $wallets = auth()->user()->wallets()
                ->whereIn('type', ['main', 'chips', 'bonus'])
                ->get()
                ->keyBy('type');

            $walletBalances = [
                'main'  => (float) ($wallets->get('main')?->balance ?? 0),
                'chips' => (float) ($wallets->get('chips')?->balance ?? 0),
                'bonus' => (float) ($wallets->get('bonus')?->balance ?? 0),
            ];
        }

        return view('home', [
            'title' => 'Home',
            'currency' => $currency,
            'providers' => $providers,
            'providerGroups' => $providerGroups,
            'productGroups' => $productGroups,
            'hotGames' => $hotGames,
            'walletBalances' => $walletBalances,
        ]);
    }

    /**
     * Convert a game product_group_name into one of the 4 parent keys.
     * Returns null if not mapped.
     */
    private static function toParentKey(string $label): ?string
    {
        $child = self::slugKey($label);

        static $lookup = null;
        if ($lookup === null) {
            $lookup = [];
            foreach (self::GROUPS as $parentKey => $cfg) {
                foreach ($cfg['children'] as $childKey) {
                    $lookup[$childKey] = $parentKey;
                }
            }
        }

        return $lookup[$child] ?? null;
    }

    private static function slugKey(string $label): string
    {
        $label = mb_strtolower(trim($label));
        $label = preg_replace('/[^\p{L}\p{N}]+/u', '-', $label) ?: '';
        $label = trim($label, '-');
        return $label !== '' ? $label : 'other';
    }
}
