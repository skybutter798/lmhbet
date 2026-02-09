<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('deposit_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('deposit_requests', 'promotion_id')) {
                $table->foreignId('promotion_id')
                    ->nullable()
                    ->after('bank_name')
                    ->constrained('promotions')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('deposit_requests', function (Blueprint $table) {
            if (Schema::hasColumn('deposit_requests', 'promotion_id')) {
                $table->dropConstrainedForeignId('promotion_id');
            }
        });
    }
};
