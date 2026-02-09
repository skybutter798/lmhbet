<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Make sure sort_order can handle 1800+ games with gaps (e.g. 1000, 2000...)
        // Use UNSIGNED to keep all values >= 0 (works with our new controller logic).
        DB::statement("ALTER TABLE dbox_games MODIFY sort_order INT UNSIGNED NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE dbox_providers MODIFY sort_order INT UNSIGNED NOT NULL DEFAULT 0");

        // Optional but highly recommended for fast sorting/filtering by provider
        // (safe even if you already have an index, but may error if duplicate name exists)
        try {
            DB::statement("CREATE INDEX idx_dbox_games_provider_sort ON dbox_games(provider_id, sort_order)");
        } catch (\Throwable $e) {
            // ignore if index already exists
        }
    }

    public function down(): void
    {
        // Rollback: keep it as INT UNSIGNED (or adjust to your previous type if you know it)
        DB::statement("ALTER TABLE dbox_games MODIFY sort_order INT UNSIGNED NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE dbox_providers MODIFY sort_order INT UNSIGNED NOT NULL DEFAULT 0");

        try {
            DB::statement("DROP INDEX idx_dbox_games_provider_sort ON dbox_games");
        } catch (\Throwable $e) {
            // ignore
        }
    }
};
