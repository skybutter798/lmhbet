<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('winpay_deposits', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->index();

            $table->string('bill_number', 64)->unique();      // 你的商户订单号
            $table->string('type', 8);                       // 01/03/...
            $table->string('bank_name', 64)->nullable();
            $table->string('depositor_name', 128)->nullable();

            $table->decimal('amount', 14, 2);

            $table->string('status', 32)->default('created'); // created/pending/paid/failed
            $table->string('winpay_status', 64)->nullable();  // 原始状态：等待/待确认/已完成/失败 ...
            $table->string('pay_url', 1024)->nullable();

            $table->json('request_payload')->nullable();
            $table->json('create_response')->nullable();
            $table->json('notify_payload')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('winpay_deposits');
    }
};
