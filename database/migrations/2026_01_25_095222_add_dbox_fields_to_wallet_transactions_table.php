<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // Keep lengths index-safe on utf8mb4
            $table->string('provider', 10)->nullable()->after('reference');     // AMS
            $table->string('round_ref', 191)->nullable()->after('provider');   // shared bet+settle key
            $table->string('bet_id', 191)->nullable()->after('round_ref');     // betId if provided
            $table->string('game_code', 191)->nullable()->after('bet_id');    // gmeCode if provided

            // Idempotency: prevents duplicates under concurrent retries
            // NULL reference can repeat; non-NULL must be unique per user
            $table->unique(['user_id', 'reference'], 'wallet_tx_user_reference_unique');

            // Fast linking / reporting
            $table->index(['user_id', 'round_ref', 'created_at'], 'wallet_tx_user_roundref_created_idx');
            $table->index(['provider', 'round_ref'], 'wallet_tx_provider_roundref_idx');
            $table->index(['bet_id'], 'wallet_tx_bet_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropIndex('wallet_tx_user_roundref_created_idx');
            $table->dropIndex('wallet_tx_provider_roundref_idx');
            $table->dropIndex('wallet_tx_bet_id_idx');

            $table->dropUnique('wallet_tx_user_reference_unique');

            $table->dropColumn(['provider', 'round_ref', 'bet_id', 'game_code']);
        });
    }
};
