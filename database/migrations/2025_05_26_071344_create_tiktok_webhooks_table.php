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
        if (!Schema::hasTable('tiktok_webhooks')) {
            Schema::create('tiktok_webhooks', function (Blueprint $table) {
                $table->id();
                $table->string('shop_id', 100)->nullable();
                $table->unsignedInteger('type_id')->nullable();
                $table->string('event_type', 100)->nullable();
                $table->json('event_data')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiktok_webhooks');
    }
};
