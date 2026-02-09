<?php

namespace App\Http\Controllers;

use App\Models\DBOXGame;
use App\Models\DBOXProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $currency = auth()->user()->currency ?? 'MYR';

        $providers = DBOXProvider::where('is_active', true)
                ->with('primaryImage')
                ->ordered()
                ->get();


        $productGroups = Cache::remember("home.productGroups.v2.$currency", 600, function () use ($currency) {
            return DBOXGame::query()
                ->selectRaw('product_group_name, COUNT(*) as cnt')
                ->where('is_active', true)
                // ->where('supports_launch', true)  // ❌ REMOVE (this was filtering to hot only)
                ->whereNotNull('product_group_name')
                ->where('product_group_name', '<>', '')
                ->whereHas('currencies', fn($q) => $q->where('currency', $currency)->where('is_active', true))
                ->groupBy('product_group_name')
                ->orderByDesc('cnt')
                ->get()
                ->map(fn($row) => [
                    'key'   => self::slugKey((string) $row->product_group_name),
                    'label' => (string) $row->product_group_name,
                    'count' => (int) $row->cnt,
                ])
                ->values();
        });


        $providerGroups = Cache::remember("home.providerGroups.v2.$currency", 600, function () use ($currency) {
            $rows = DBOXGame::query()
                ->selectRaw('provider_id, product_group_name, COUNT(*) as cnt')
                ->where('is_active', true)
                // ->where('supports_launch', true) // ❌ REMOVE (this was filtering to hot only)
                ->whereNotNull('product_group_name')
                ->where('product_group_name', '<>', '')
                ->whereHas('currencies', fn($q) => $q->where('currency', $currency)->where('is_active', true))
                ->groupBy('provider_id', 'product_group_name')
                ->get();
        
            $map = [];
            foreach ($rows as $r) {
                $pid = (int) $r->provider_id;
                $label = (string) $r->product_group_name;
                $key = self::slugKey($label);
                $cnt = (int) $r->cnt;
        
                $map[$pid] ??= ['items' => []];
                $map[$pid]['items'][$key] = [
                    'key' => $key,
                    'label' => $label,
                    'cnt' => ($map[$pid]['items'][$key]['cnt'] ?? 0) + $cnt,
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
            ->whereHas('currencies', fn ($q) => $q->where('currency', $currency)->where('is_active', true))
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

    private static function slugKey(string $label): string
    {
        $label = mb_strtolower(trim($label));
        $label = preg_replace('/[^\p{L}\p{N}]+/u', '-', $label) ?: '';
        $label = trim($label, '-');
        return $label !== '' ? $label : 'other';
    }
}
