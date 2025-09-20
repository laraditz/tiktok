<?php

use Illuminate\Support\Facades\Schema;
use Laraditz\TikTok\Models\TiktokShop;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tiktok_orders')) {
            Schema::create('tiktok_orders', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('shop_id', 100)->nullable();
                $table->string('status', 50)->nullable();
                $table->tinyInteger('is_on_hold_order')->nullable();
                $table->unsignedBigInteger('update_time')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiktok_orders');
    }
};
