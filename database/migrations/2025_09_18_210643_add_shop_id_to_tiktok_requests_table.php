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
        Schema::table('tiktok_requests', function (Blueprint $table) {
            $table->string('shop_id', 100)->nullable()->after('id');

            $table->index('shop_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tiktok_requests', function (Blueprint $table) {
            //
        });
    }
};
