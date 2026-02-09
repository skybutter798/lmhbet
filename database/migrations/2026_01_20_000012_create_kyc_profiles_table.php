<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // 0 not_submitted, 1 pending, 2 approved, 3 rejected
            $table->unsignedTinyInteger('status')->default(0);

            $table->string('full_name', 120)->nullable();
            $table->string('id_type', 30)->nullable(); // mykad/passport/etc
            $table->string('id_number', 80)->nullable();
            $table->date('dob')->nullable();

            $table->string('document_front_path', 255)->nullable();
            $table->string('document_back_path', 255)->nullable();
            $table->string('selfie_path', 255)->nullable();

            $table->text('remark')->nullable(); // rejection reason

            $table->timestamps();

            $table->unique('user_id');
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_profiles');
    }
};
