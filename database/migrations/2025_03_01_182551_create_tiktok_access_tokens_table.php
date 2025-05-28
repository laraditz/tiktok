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
        Schema::create('tiktok_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('subjectable');
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->datetime('expires_at')->nullable();
            $table->datetime('refresh_expires_at')->nullable();
            $table->string('open_id')->nullable();
            $table->string('seller_name')->nullable();
            $table->string('seller_base_region', 50)->nullable();
            $table->tinyInteger('user_type')->nullable();
            $table->json('granted_scopes')->nullable();
            $table->string('code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiktok_access_tokens');
    }
};
