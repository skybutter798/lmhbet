<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dbox_game_imgs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('game_id')
                ->constrained('dbox_games')
                ->cascadeOnDelete();

            // Store relative public path like: images/games/xxx.webp
            $table->string('path', 255);

            $table->string('label', 100)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['game_id', 'path'], 'dbox_game_imgs_game_path_unique');
            $table->index(['game_id', 'is_primary'], 'dbox_game_imgs_game_primary_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dbox_game_imgs');
    }
};
