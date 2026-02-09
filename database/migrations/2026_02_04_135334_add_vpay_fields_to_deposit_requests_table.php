<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('deposit_requests', function (Blueprint $table) {
            $table->string('provider')->nullable()->index();           // 'vpay'
            $table->string('out_trade_no')->nullable()->index();       // your ref
            $table->string('trade_no')->nullable()->index();           // platform trade no
            $table->string('pay_url', 1000)->nullable();
            $table->string('trade_code', 50)->nullable();              // '36' duitnow etc
            $table->timestamp('paid_at')->nullable();
            $table->json('provider_payload')->nullable();              // raw notify/order response
        });
    }

    public function down(): void
    {
        Schema::table('deposit_requests', function (Blueprint $table) {
            $table->dropColumn([
                'provider','out_trade_no','trade_no','pay_url','trade_code','paid_at','provider_payload'
            ]);
        });
    }
};