<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('default_withdrawal_bank_account_id')->nullable()->after('pin');

            // optional FK (keeps referential integrity)
            $table->foreign('default_withdrawal_bank_account_id')
                ->references('id')
                ->on('withdrawal_bank_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['default_withdrawal_bank_account_id']);
            $table->dropColumn('default_withdrawal_bank_account_id');
        });
    }
};