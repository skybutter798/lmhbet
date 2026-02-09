<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dbox_provider_imgs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_id')
                ->constrained('dbox_providers')
                ->cascadeOnDelete();

            // Store relative public path like: images/providers/xxx.png
            $table->string('path', 255);

            $table->string('label', 100)->nullable();
            $table->boolean('is_primary')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['provider_id', 'path'], 'dbox_provider_imgs_provider_path_unique');
            $table->index(['provider_id', 'is_primary'], 'dbox_provider_imgs_provider_primary_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dbox_provider_imgs');
    }
};
