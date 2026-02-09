<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // main / chips / bonus / promote / extra
            $table->string('type', 20);

            // use decimal for money-like values; adjust scale/precision if needed
            $table->decimal('balance', 36, 18)->default(0);

            // 0 inactive, 1 pending, 2 active
            $table->unsignedTinyInteger('status')->default(1);

            // optional: lock a wallet (admin/security)
            $table->timestamp('locked_until')->nullable();

            $table->timestamps();

            // one wallet per type per user
            $table->unique(['user_id', 'type']);

            $table->index(['user_id', 'status']);
            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
