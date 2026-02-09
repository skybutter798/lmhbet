<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Drop unique first (safe because you named it)
        DB::statement("ALTER TABLE wallet_transactions DROP INDEX wallet_tx_user_reference_unique");

        // Expand columns to match DBOX limits (<= 200)
        DB::statement("ALTER TABLE wallet_transactions MODIFY reference VARCHAR(200) NULL");
        DB::statement("ALTER TABLE wallet_transactions MODIFY round_ref VARCHAR(200) NULL");
        DB::statement("ALTER TABLE wallet_transactions MODIFY bet_id VARCHAR(200) NULL");

        DB::statement("ALTER TABLE bet_records MODIFY round_ref VARCHAR(200) NOT NULL");
        DB::statement("ALTER TABLE bet_records MODIFY bet_id VARCHAR(200) NULL");

        // Re-create unique
        DB::statement("ALTER TABLE wallet_transactions ADD UNIQUE INDEX wallet_tx_user_reference_unique (user_id, reference)");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE wallet_transactions DROP INDEX wallet_tx_user_reference_unique");

        DB::statement("ALTER TABLE wallet_transactions MODIFY reference VARCHAR(100) NULL");
        DB::statement("ALTER TABLE wallet_transactions MODIFY round_ref VARCHAR(191) NULL");
        DB::statement("ALTER TABLE wallet_transactions MODIFY bet_id VARCHAR(191) NULL");

        DB::statement("ALTER TABLE bet_records MODIFY round_ref VARCHAR(191) NOT NULL");
        DB::statement("ALTER TABLE bet_records MODIFY bet_id VARCHAR(191) NULL");

        DB::statement("ALTER TABLE wallet_transactions ADD UNIQUE INDEX wallet_tx_user_reference_unique (user_id, reference)");
    }
};
