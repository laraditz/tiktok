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
        if (!Schema::hasTable('tiktok_event_webhooks')) {
            Schema::create('tiktok_event_webhooks', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('shop_id')->unsigned();
                $table->string('event_type', 100);
                $table->string('address')->nullable();
                $table->timestamps();

                $table->foreign('shop_id')->references('id')->on('tiktok_shops');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiktok_event_webhooks');
    }
};
