<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vip_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // Player, VIP1, VIP2...
            $table->unsignedInteger('level')->default(0);
            $table->timestamps();

            $table->unique('name');
            $table->unique('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vip_tiers');
    }
};
