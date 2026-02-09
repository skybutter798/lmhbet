<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Insert / update a couple of promotions (idempotent)
        $promotions = [
            [
                'code' => 'TOPUP_REBATE_0_5',
                'title' => 'Top Up Rebate 0.50%',
                'type' => 'topup_rebate',
                'bonus_type' => 'percent',
                'bonus_value' => 0.50,
                'bonus_cap' => null,
                'min_amount' => 10.00,
                'max_amount' => null,
                'turnover_multiplier' => 1.00,
                'currency' => 'MYR',
                'terms' => 'Rebate credited after top up. Subject to review.',
                'is_active' => true,
                'starts_at' => $now,
                'ends_at' => null,
                'sort_order' => 10,
            ],
            [
                'code' => 'DEPOSIT_BONUS_68',
                'title' => 'Deposit Bonus 68%',
                'type' => 'deposit',
                'bonus_type' => 'percent',
                'bonus_value' => 68.00,
                'bonus_cap' => 200.00,
                'min_amount' => 30.00,
                'max_amount' => null,
                'turnover_multiplier' => 15.00,
                'currency' => 'MYR',
                'terms' => 'Bonus subject to x15 turnover requirement. T&C apply.',
                'is_active' => true,
                'starts_at' => $now,
                'ends_at' => null,
                'sort_order' => 20,
            ],
        ];

        foreach ($promotions as $p) {
            DB::table('promotions')->updateOrInsert(
                ['code' => $p['code']],
                array_merge($p, [
                    'updated_at' => $now,
                    'created_at' => $now, // updateOrInsert requires you to set it if you want it on first insert
                ])
            );
        }

        // OPTIONAL: attach promotions to a DBOX provider (pivot table)
        // If you have one provider record (e.g. code = 'DBOX'), this will link them.
        // Adjust the where() condition to match your dbox_providers schema.
        $provider = DB::table('dbox_providers')->where('code', 'DBOX')->first();

        if ($provider) {
            $promoIds = DB::table('promotions')
                ->whereIn('code', ['TOPUP_REBATE_0_5', 'DEPOSIT_BONUS_68'])
                ->pluck('id')
                ->all();

            foreach ($promoIds as $promoId) {
                DB::table('dbox_provider_promotion')->updateOrInsert(
                    [
                        'provider_id' => $provider->id,
                        'promotion_id' => $promoId,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }
}
