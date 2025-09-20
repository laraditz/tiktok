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
        if (!Schema::hasTable('tiktok_products')) {
            Schema::create('tiktok_products', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('shop_id', 100)->nullable();
                $table->string('title')->nullable();
                $table->string('status', 50)->nullable();
                $table->tinyInteger('has_draft')->nullable();
                $table->tinyInteger('is_not_for_sale')->nullable();
                $table->json('sales_regions')->nullable();
                $table->json('audit')->nullable();
                $table->integer('create_time')->nullable();
                $table->integer('update_time')->nullable();
                $table->timestamps();

                $table->index('shop_id');
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiktok_products');
    }
};
