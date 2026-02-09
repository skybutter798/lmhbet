<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bet_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // DBOX/provider
            $table->string('provider', 10)->nullable();     // prvCode
            $table->string('round_ref', 191);              // stable round key
            $table->string('bet_id', 191)->nullable();
            $table->string('game_code', 191)->nullable();
            $table->string('currency', 3)->nullable();

            $table->string('wallet_type', 20)->default('chips');

            // amounts (store high precision, cast in model)
            $table->decimal('stake_amount', 36, 18)->default('0');
            $table->decimal('payout_amount', 36, 18)->default('0');
            $table->decimal('profit_amount', 36, 18)->default('0'); // payout - stake

            // link back to seamless callbacks
            $table->string('bet_reference', 191)->nullable();    // bet unqTxnId
            $table->string('settle_reference', 191)->nullable(); // settle unqTxnId

            $table->timestamp('bet_at')->nullable();
            $table->timestamp('settled_at')->nullable();

            // open | settled | cancelled | void | etc
            $table->string('status', 20)->default('open');

            $table->json('meta')->nullable();

            $table->timestamps();

            // one row per (user, provider, round)
            $table->unique(['user_id', 'provider', 'round_ref'], 'bet_records_user_provider_round_unique');

            $table->index(['user_id', 'bet_at'], 'bet_records_user_betat_idx');
            $table->index(['provider', 'bet_at'], 'bet_records_provider_betat_idx');
            $table->index(['bet_id'], 'bet_records_betid_idx');
            $table->index(['game_code'], 'bet_records_gamecode_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bet_records');
    }
};
