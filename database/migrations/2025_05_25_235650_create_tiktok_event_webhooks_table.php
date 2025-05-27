<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laraditz\TikTok\Models\TiktokShop;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tiktok_event_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TiktokShop::class, 'shop_id');
            $table->string('event_type', 100);
            $table->string('address')->nullable();
            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('tiktok_shops');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiktok_event_webhooks');
    }
};
