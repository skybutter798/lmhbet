<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();

            $table->string('username')->unique();
            $table->string('password');

            // Admin PIN (store hashed, not plain)
            $table->string('pin');

            // 2FA secret can be null
            $table->string('two_fa_secret')->nullable();

            // upline admin
            $table->foreignId('upline_id')->nullable()->constrained('admins')->nullOnDelete();

            // group + role
            $table->string('group')->nullable(); // or rename to group_name if you prefer
            $table->string('role')->default('admin'); // superadmin/admin/support etc

            // status
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
