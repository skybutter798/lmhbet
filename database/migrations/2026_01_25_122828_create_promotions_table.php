<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_promotions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();

            // optional stable identifier (for future mapping / admin)
            $table->string('code', 80)->nullable()->unique();

            // shown in UI: e.g. "Top Up Rebate 0.50%", "Deposit Bonus 68%"
            $table->string('title', 200);

            // e.g. deposit, topup_rebate, slot_deposit_bonus (free-form)
            $table->string('type', 50)->default('deposit');

            // percent = bonus_value is % ; fixed = bonus_value is amount
            $table->string('bonus_type', 20)->default('percent'); // percent|fixed
            $table->decimal('bonus_value', 12, 4)->default(0);   // 0.5, 68, 30 etc

            // for percent bonus (optional)
            $table->decimal('bonus_cap', 18, 2)->nullable();

            // eligibility limits (optional)
            $table->decimal('min_amount', 18, 2)->nullable();
            $table->decimal('max_amount', 18, 2)->nullable();

            // screenshot: x1, x15, x20 (can be non-integer if needed)
            $table->decimal('turnover_multiplier', 10, 2)->default(1);

            // optional (NULL = all currencies)
            $table->string('currency', 10)->nullable();

            $table->text('terms')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at'], 'promotions_active_window_idx');
            $table->index(['type', 'currency'], 'promotions_type_currency_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
