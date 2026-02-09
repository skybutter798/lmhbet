<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('admin_id')
                ->constrained('admins')
                ->cascadeOnDelete();

            $table->string('action', 60); // user.toggle_active, user.ban, kyc.approve, wallet.adjust, etc

            $table->unsignedBigInteger('target_user_id')->nullable()->index();
            $table->string('target_type', 40)->nullable(); // users, wallets, kyc_submissions etc
            $table->unsignedBigInteger('target_id')->nullable(); // row id in target table if any

            $table->ipAddress('ip')->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
    }
};
