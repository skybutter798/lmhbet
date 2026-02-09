<?php
// database/migrations/2026_01_25_122828_create_dbox_provider_promotion_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // avoid crash if table left behind
        if (Schema::hasTable('dbox_provider_promotion')) {
            return;
        }

        Schema::create('dbox_provider_promotion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_id')
                ->constrained('dbox_providers')
                ->cascadeOnDelete();

            $table->foreignId('promotion_id')
                ->constrained('promotions')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['provider_id', 'promotion_id'], 'dbox_provider_promotion_unique');
            $table->index('promotion_id', 'dbox_provider_promotion_promotion_idx');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('dbox_provider_promotion');
        Schema::enableForeignKeyConstraints();
    }
};
