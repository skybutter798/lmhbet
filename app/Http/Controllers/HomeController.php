<?php

namespace App\Http\Controllers;

use App\Models\DBOXGame;
use App\Models\DBOXProvider;
use App\Models\DepositRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
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
        $currency = auth()->check()
            ? (auth()->user()->currency ?? 'MYR')
            : 'MYR';

        $providers = DBOXProvider::where('is_active', true)
            ->with('primaryImage')
            ->ordered()
            ->get();

        $productGroups = Cache::remember("home.productGroups.v3.$currency", 600, function () use ($currency) {
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
                    continue;
                }
                $agg[$parentKey] += (int) $r->cnt;
            }

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