<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tiktok_shops', function (Blueprint $table) {
            $table->id();
            $table->string('identifier', 80)->nullable();
            $table->string('code', 20)->nullable();
            $table->string('name')->nullable();
            $table->string('region', 10)->nullable();
            $table->string('seller_type', 50)->nullable();
            $table->string('cipher')->nullable();
            $table->string('open_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiktok_sellers');
    }
};
