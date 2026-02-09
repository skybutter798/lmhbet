<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('deposit_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('deposit_requests', 'bonus_amount')) {
                $table->decimal('bonus_amount', 18, 2)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('deposit_requests', 'turnover_required')) {
                $table->decimal('turnover_required', 18, 2)->nullable()->after('bonus_amount');
            }
            if (!Schema::hasColumn('deposit_requests', 'turnover_progress')) {
                $table->decimal('turnover_progress', 18, 2)->default(0)->after('turnover_required');
            }
            if (!Schema::hasColumn('deposit_requests', 'bonus_status')) {
                $table->string('bonus_status', 20)->default('none')->after('turnover_progress');
                // none | in_progress | done | cancelled
            }
            if (!Schema::hasColumn('deposit_requests', 'bonus_done_at')) {
                $table->timestamp('bonus_done_at')->nullable()->after('bonus_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deposit_requests', function (Blueprint $table) {
            $drops = [];
            foreach (['bonus_amount','turnover_required','turnover_progress','bonus_status','bonus_done_at'] as $c) {
                if (Schema::hasColumn('deposit_requests', $c)) $drops[] = $c;
            }
            if ($drops) $table->dropColumn($drops);
        });
    }
};