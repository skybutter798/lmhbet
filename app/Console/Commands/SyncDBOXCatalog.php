<?php

namespace App\Console\Commands;

use App\Models\DBOXGame;
use App\Models\DBOXGameCurrency;
use App\Models\DBOXProvider;
use App\Services\DBOX\DBOXClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncDBOXCatalog extends Command
{
    protected $signature = 'dbox:sync-catalog {--lnchGme=true}';
    protected $description = 'Sync DBOX provider and game catalog into local DB';

    public function handle(): int
    {
        $dbox = DBOXClient::makeFromConfig();
        $now = now();

        $currencies = array_filter(array_map('trim', explode(',', (string) env('DBOX_SYNC_CURRENCIES', 'MYR'))));
        if (!$currencies) $currencies = ['MYR'];

        $lnchGmeOpt = $this->option('lnchGme');
        $lnchGme = filter_var($lnchGmeOpt, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($lnchGme === null) $lnchGme = true;

        $this->info('Sync currencies: '.implode(',', $currencies));
        $this->info('Filter lnchGme: '.($lnchGme ? 'true' : 'false'));

        $provRes = $dbox->get('/mer/eai/cmo/provider-list');
        $provJson = $provRes->json();

        if (!is_array($provJson) || ($provJson['code'] ?? -1) !== 0) {
            $this->error('Provider list failed: '.substr($provRes->body(), 0, 300));
            return 1;
        }

        $providers = $provJson['data'] ?? [];
        $this->info('Providers: '.count($providers));

        DB::transaction(function () use ($providers, $currencies, $lnchGme, $dbox, $now) {

            // Upsert providers
            foreach ($providers as $p) {
                if (!isset($p['prvCode'])) continue;

                DBOXProvider::updateOrCreate(
                    ['code' => $p['prvCode']],
                    [
                        'name' => $p['prvNm'] ?? $p['prvCode'],
                        'is_active' => true,
                        'last_synced_at' => $now,
                    ]
                );
            }

            // Optional: mark providers not in list as inactive
            $codes = array_values(array_filter(array_map(fn($p) => $p['prvCode'] ?? null, $providers)));
            if ($codes) {
                DBOXProvider::whereNotIn('code', $codes)->update(['is_active' => false]);
            }

            // For each provider + currency pull game list
            $activeProviders = DBOXProvider::whereIn('code', $codes)->get();

            foreach ($activeProviders as $provider) {
                foreach ($currencies as $currency) {
                    $res = $dbox->get('/mer/eai/cmo/game-list', [
                        'prvCode' => $provider->code,
                        'curCode' => $currency,
                        'lnchGme' => $lnchGme ? 'true' : 'false',
                    ]);

                    $json = $res->json();
                    if (!is_array($json) || ($json['code'] ?? -1) !== 0) {
                        // Don’t kill entire sync; just continue
                        continue;
                    }

                    $games = $json['data'] ?? [];

                    // track seen IDs for this provider+currency run
                    $seenGameIds = [];

                    foreach ($games as $g) {
                        if (!isset($g['gmeCode'])) continue;

                        $game = DBOXGame::updateOrCreate(
                            [
                                'provider_id' => $provider->id,
                                'code' => $g['gmeCode'],
                            ],
                            [
                                'name' => $g['gmeNm'] ?? $g['gmeCode'],
                                'product_group' => $g['productGroup'] ?? null,
                                'sub_product_group' => $g['subProductGroup'] ?? null,
                                'product_group_name' => $g['productGroupName'] ?? null,
                                'sub_product_group_name' => $g['subProductGroupName'] ?? null,
                                'supports_launch' => (bool) ($g['lnchGme'] ?? false),
                                'is_active' => true,
                                'last_seen_at' => $now,
                            ]
                        );

                        $seenGameIds[] = $game->id;

                        DBOXGameCurrency::updateOrCreate(
                            ['game_id' => $game->id, 'currency' => $currency],
                            ['is_active' => true, 'last_seen_at' => $now]
                        );
                    }

                    // Mark currencies inactive if not seen in this run
                    if ($seenGameIds) {
                        DBOXGameCurrency::where('currency', $currency)
                            ->whereHas('game', fn($q) => $q->where('provider_id', $provider->id))
                            ->whereNotIn('game_id', $seenGameIds)
                            ->update(['is_active' => false]);
                    }
                }
            }
        });

        $this->info('Sync done.');
        return 0;
    }
}
