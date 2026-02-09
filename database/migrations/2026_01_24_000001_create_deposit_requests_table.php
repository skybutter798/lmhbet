<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deposit_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();

            $table->string('currency', 8)->default('MYR');
            $table->string('method', 32)->default('bank_transfer'); // bank_transfer | e_wallet
            $table->string('bank_name', 80)->nullable();            // Maybank, CIMB, etc
            $table->decimal('amount', 18, 2);

            $table->string('status', 20)->default('pending');       // pending | approved | rejected | cancelled
            $table->string('reference', 64)->nullable();            // optional ref shown in history
            $table->text('remark')->nullable();                     // optional

            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_requests');
    }
};
