<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('wallet_id')
                ->constrained('wallets')
                ->cascadeOnDelete();

            // snapshot of wallet type at time of tx (useful for reporting even if wallet changes)
            $table->string('wallet_type', 20);

            // credit = add, debit = deduct
            $table->string('direction', 10); // 'credit' | 'debit'

            $table->decimal('amount', 36, 18);
            $table->decimal('balance_before', 36, 18);
            $table->decimal('balance_after', 36, 18);

            // status: 0 pending, 1 completed, 2 reversed, 3 failed, 4 cancelled
            $table->unsignedTinyInteger('status')->default(1);

            // idempotency / external reference keys
            $table->string('reference', 100)->nullable(); // e.g. "deposit:123", "order:999", etc.
            $table->string('external_id', 100)->nullable(); // payment gateway id / provider tx id
            $table->string('tx_hash', 100)->nullable(); // blockchain hash if any

            $table->string('title', 120)->nullable(); // short label
            $table->text('description')->nullable(); // longer note

            // who/what created it
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            // for audit/debug
            $table->ipAddress('ip')->nullable();
            $table->string('user_agent', 255)->nullable();

            // flexible extra data
            $table->json('meta')->nullable();

            // if you want “effective date” different from created_at
            $table->timestamp('occurred_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'wallet_id', 'created_at']);
            $table->index(['user_id', 'wallet_type', 'created_at']);
            $table->index(['reference']);
            $table->index(['external_id']);
            $table->index(['tx_hash']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
