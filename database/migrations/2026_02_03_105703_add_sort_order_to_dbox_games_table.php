<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dbox_games', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('name');
            $table->index('sort_order');
            // Optional (recommended if you sort within provider a lot):
            $table->index(['provider_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('dbox_games', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropIndex(['provider_id', 'sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
