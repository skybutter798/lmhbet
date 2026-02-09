<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VipTier;

class VipTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            ['name' => 'Player', 'level' => 0],
            ['name' => 'VIP1', 'level' => 1],
            ['name' => 'VIP2', 'level' => 2],
            ['name' => 'VIP3', 'level' => 3],
        ];

        foreach ($tiers as $t) {
            VipTier::updateOrCreate(
                ['level' => $t['level']],
                ['name' => $t['name']]
            );
        }
    }
}
