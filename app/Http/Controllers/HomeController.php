<?php

namespace App\Http\Controllers;

use App\Models\DBOXGame;
use App\Models\DBOXProvider;
use App\Models\DepositRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    private const TAB_ORDER = ['slots', 'sport', 'lottery', 'casino'];

    private const GROUPS = [
        'slots' => [
            'label' => 'Slots',
            'children' => ['slot', 'arcade', 'table-game', 'card-game', 'fishing'],
        ],
        'sport' => [
            'label' => 'Sports',
            'children' => ['sportsbook'],
        ],
        'lottery' => [
            'label' => 'Lottery',
            'children' => ['lottery', 'bingo'],
        ],
        'casino' => [
            'label' => 'Casino',
            'children' => ['live-dealer', 'lobby'],
        ],
    ];

    /**
     * ✅ Set category by provider ID (edit this map)
     * provider_id => parentKey (slots|sport|lottery|casino)
     */
    private const PROVIDER_CATEGORY_BY_ID = [
        // Slots
        2 => 'slots',
        8 => 'slots',
        10 => 'slots',
        13 => 'slots',
        17 => 'slots',
        18 => 'slots',
        21 => 'slots',
    
        // Sports
        4  => 'sport',
        6  => 'sport',
        11  => 'sport',
        12 => 'sport',
        20 => 'sport',
    
        // Lottery
        3 => 'lottery',
    
        // Casino
        5 => 'casino',
        7 => 'casino',
        15 => 'casino',
        16 => 'casino',
        19 => 'casino',
    ];

    public function index(): View
    {
        $currency = auth()->check()
            ? (auth()->user()->currency ?? 'MYR')
            : 'MYR';

        $providers = DBOXProvider::where('is_active', true)
            ->with('primaryImage')
            ->ordered()
            ->get();

        $providerGroups = Cache::remember("home.providerGroups.v4.$currency", 600, function () use ($currency) {
            $activeProviderIds = DBOXProvider::where('is_active', true)->pluck('id')->map(fn ($x) => (int) $x)->all();

            $map = [];
            foreach ($activeProviderIds as $pid) {
                $map[$pid] = ['items' => []];
            }

            // ✅ Primary: provider_id => category
            foreach (self::PROVIDER_CATEGORY_BY_ID as $pid => $parentKey) {
                $pid = (int) $pid;
                $parentKey = (string) $parentKey;

                if (!isset($map[$pid])) {
                    continue;
                }
                if (!isset(self::GROUPS[$parentKey])) {
                    continue;
                }

                $map[$pid]['items'][$parentKey] = [
                    'key' => $parentKey,
                    'label' => self::GROUPS[$parentKey]['label'],
                    'cnt' => PHP_INT_MAX, // keep first (only matters if you add multiple categories per provider)
                ];
            }

            // Fallback only for providers not mapped above
            $needFallback = [];
            foreach ($activeProviderIds as $pid) {
                if (empty($map[$pid]['items'])) {
                    $needFallback[] = $pid;
                }
            }

            if (!empty($needFallback)) {
                $rows = DBOXGame::query()
                    ->selectRaw('provider_id, product_group_name, COUNT(*) as cnt')
                    ->where('is_active', true)
                    ->whereIn('provider_id', $needFallback)
                    ->whereNotNull('product_group_name')
                    ->where('product_group_name', '<>', '')
                    ->whereHas('currencies', fn ($q) => $q->where('currency', $currency)->where('is_active', true))
                    ->groupBy('provider_id', 'product_group_name')
                    ->get();

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
            }

            $out = [];
            foreach ($map as $pid => $data) {
                $items = array_values($data['items']);

                if (!$items) {
                    $out[$pid] = [
                        'keys' => ['other'],
                        'labels' => ['Other'],
                    ];
                    continue;
                }

                usort($items, fn ($a, $b) => $b['cnt'] <=> $a['cnt']);

                $out[$pid] = [
                    'keys' => array_map(fn ($x) => $x['key'], $items),
                    'labels' => array_map(fn ($x) => $x['label'], $items),
                ];
            }

            return $out;
        });

        // ✅ Tabs in fixed order: Slots, Sports, Lottery, Casino
        $counts = array_fill_keys(self::TAB_ORDER, 0);
        foreach ($providers as $p) {
            $keys = $providerGroups[$p->id]['keys'] ?? [];
            foreach ($keys as $k) {
                if (isset($counts[$k])) {
                    $counts[$k]++;
                }
            }
        }

        $productGroups = collect(array_map(function ($k) use ($counts) {
            return [
                'key' => $k,
                'label' => self::GROUPS[$k]['label'],
                'count' => (int) ($counts[$k] ?? 0),
            ];
        }, self::TAB_ORDER));

        $hotGames = DBOXGame::query()
            ->where('is_active', true)
            ->where('supports_launch', true)
            ->whereHas('currencies', fn ($q) => $q->where('currency', $currency)->where('is_active', true))
            ->with(['provider', 'primaryImage'])
            ->ordered()
            ->limit(20)
            ->get();

        $walletBalances = [
            'main' => 0.0,
            'chips' => 0.0,
            'bonus' => 0.0,
        ];

        if (auth()->check()) {
            $user = auth()->user();

            $wallets = $user->wallets()
                ->whereIn('type', ['main', 'chips'])
                ->get()
                ->keyBy('type');

            $pendingBonus = (float) DepositRequest::query()
                ->where('user_id', $user->id)
                ->whereNotNull('promotion_id')
                ->where('status', DepositRequest::STATUS_APPROVED)
                ->where('bonus_status', 'in_progress')
                ->sum('bonus_amount');

            $walletBalances = [
                'main'  => (float) ($wallets->get('main')?->balance ?? 0),
                'chips' => (float) ($wallets->get('chips')?->balance ?? 0),
                'bonus' => (float) $pendingBonus,
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
