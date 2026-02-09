<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kyc_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('bank_name', 80);
            $table->string('account_holder_name', 191);
            $table->string('account_number', 32);

            // draft | pending | approved | rejected | cancelled
            $table->string('status', 20)->default('pending');
            $table->string('remarks', 255)->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_submissions');
    }
};
