<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bonus_turnover_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deposit_request_id');
            $table->unsignedBigInteger('bet_record_id');
            $table->decimal('counted_amount', 18, 2)->default(0);
            $table->timestamps();

            $table->unique('bet_record_id'); // ✅ each bet only counted once (oldest active bonus)
            $table->index(['deposit_request_id']);

            $table->foreign('deposit_request_id')->references('id')->on('deposit_requests')->onDelete('cascade');
            $table->foreign('bet_record_id')->references('id')->on('bet_records')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_turnover_items');
    }
};