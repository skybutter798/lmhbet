<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bet_records', function (Blueprint $table) {
            $table->index(['user_id', 'bet_at']);
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'settled_at']);
        });
    }

    public function down(): void
    {
        Schema::table('bet_records', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'bet_at']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['user_id', 'settled_at']);
        });
    }
};
