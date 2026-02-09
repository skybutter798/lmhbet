<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dbox_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('dbox_providers')->cascadeOnDelete();

            $table->string('code', 80); // gmeCode
            $table->string('name', 200);

            $table->string('product_group', 50)->nullable();
            $table->string('sub_product_group', 50)->nullable();
            $table->string('product_group_name', 200)->nullable();
            $table->string('sub_product_group_name', 200)->nullable();

            $table->boolean('supports_launch')->default(false); // lnchGme
            $table->boolean('is_active')->default(true);

            $table->timestamp('last_seen_at')->nullable();

            $table->timestamps();

            $table->unique(['provider_id', 'code']);
            $table->index(['provider_id', 'product_group']);
            $table->index(['provider_id', 'sub_product_group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dbox_games');
    }
};
