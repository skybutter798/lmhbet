<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dbox_game_currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('dbox_games')->cascadeOnDelete();
            $table->string('currency', 10); // MYR, SGD, etc
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['game_id', 'currency']);
            $table->index(['currency', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dbox_game_currencies');
    }
};
