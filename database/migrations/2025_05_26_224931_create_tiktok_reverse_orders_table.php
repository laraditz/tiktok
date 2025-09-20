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
        if (!Schema::hasTable('tiktok_return_orders')) {
            Schema::create('tiktok_return_orders', function (Blueprint $table) {
                $table->id();
                $table->string('shop_id', 100)->nullable();
                $table->string('order_id', 50)->nullable();
                $table->string('role', 20)->nullable();
                $table->string('type', 50)->nullable();
                $table->string('status', 50)->nullable();
                $table->string('return_id', 50)->nullable();
                $table->unsignedBigInteger('create_time')->nullable();
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
        Schema::dropIfExists('tiktok_return_orders');
    }
};
