<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laraditz\TikTok\Models\TiktokProduct;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tiktok_product_skus')) {
            Schema::create('tiktok_product_skus', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignIdFor(TiktokProduct::class, 'product_id')->nullable();
                $table->string('seller_sku')->nullable();
                $table->json('inventory')->nullable();
                $table->json('price')->nullable();
                $table->json('status_info')->nullable();
                $table->timestamps();

                $table->index(['product_id']);
                $table->index(['seller_sku']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiktok_product_skus');
    }
};
