<?php
// /home/lmh/app/database/migrations/2026_02_10_000002_create_support_messages_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('sender_role', 20)->default('user'); // user/admin
            $table->text('body');
            $table->timestamp('read_by_user_at')->nullable();
            $table->timestamp('read_by_admin_at')->nullable();
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
            $table->index(['sender_role', 'created_at']);

            $table->foreign('ticket_id')->references('id')->on('support_tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
    }
};