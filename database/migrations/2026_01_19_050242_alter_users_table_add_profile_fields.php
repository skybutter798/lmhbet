<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Basic identity
            $table->string('username', 50)->unique()->after('id');
            $table->string('name', 120)->nullable()->change(); // allow nullable if you want username-first
            $table->string('email', 191)->nullable()->change();


            // Phone / locale
            $table->string('phone_country', 6)->nullable()->after('email'); // e.g. +60
            $table->string('phone', 32)->nullable()->unique()->after('phone_country');
            $table->string('country', 2)->nullable()->after('phone'); // ISO-3166-1 alpha2 e.g. MY
            $table->string('currency', 3)->default('MYR')->after('country'); // ISO-4217

            // Referral (generic)
            $table->string('referral_code', 32)->nullable()->unique()->after('currency');
            $table->unsignedBigInteger('referred_by_user_id')->nullable()->after('referral_code');

            // Account status
            $table->boolean('is_active')->default(true)->after('referred_by_user_id');
            $table->timestamp('banned_at')->nullable()->after('is_active');
            $table->string('ban_reason', 255)->nullable()->after('banned_at');

            // Verification flags
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');

            // Login tracking / security
            $table->timestamp('last_login_at')->nullable()->after('phone_verified_at');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->unsignedInteger('failed_login_attempts')->default(0)->after('last_login_ip');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');

            // Optional: 2FA (generic)
            $table->boolean('two_factor_enabled')->default(false)->after('locked_until');
            $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');

            // Soft deletes (optional but recommended)
            $table->softDeletes();

            // Indexes / FK
            $table->index(['country', 'currency']);
            $table->foreign('referred_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by_user_id']);
            $table->dropIndex(['country', 'currency']);

            $table->dropSoftDeletes();

            $table->dropColumn([
                'username',
                'phone_country',
                'phone',
                'country',
                'currency',
                'referral_code',
                'referred_by_user_id',
                'is_active',
                'banned_at',
                'ban_reason',
                'phone_verified_at',
                'last_login_at',
                'last_login_ip',
                'failed_login_attempts',
                'locked_until',
                'two_factor_enabled',
                'two_factor_secret',
                'two_factor_recovery_codes',
            ]);

            // revert nullable changes safely (only if you originally had them NOT NULL)
            // $table->string('name', 120)->nullable(false)->change();
            // $table->string('email', 191)->nullable(false)->unique()->change();
        });
    }
};
