<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dbox_providers', function (Blueprint $table) {
            // 0 = default (unsorted). Lower comes first (or flip, your choice).
            $table->unsignedInteger('sort_order')->default(0)->after('name');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('dbox_providers', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
