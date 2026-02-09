<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('kyc_submission_id')->nullable();

            $table->string('currency', 8)->default('MYR');
            $table->decimal('amount', 18, 2)->default(0);

            $table->string('status', 24)->default('pending'); // pending|approved|rejected|cancelled
            $table->string('remarks', 255)->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'status']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('kyc_submission_id')->references('id')->on('kyc_submissions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};
