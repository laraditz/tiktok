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
        if (!Schema::hasTable('tiktok_requests')) {
            Schema::create('tiktok_requests', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->string('action')->nullable();
                $table->text('url')->nullable();
                $table->json('request')->nullable();
                $table->string('request_id')->nullable();
                $table->string('code', 50)->nullable();
                $table->string('message')->nullable();
                $table->json('response')->nullable();
                $table->string('error')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiktok_requests');
    }
};
