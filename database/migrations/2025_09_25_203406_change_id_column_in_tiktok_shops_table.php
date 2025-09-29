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
        Schema::disableForeignKeyConstraints();

        Schema::table('tiktok_event_webhooks', function (Blueprint $table) {
            $table->dropForeign('tiktok_event_webhooks_shop_id_foreign');
        });

        Schema::table('tiktok_shops', function (Blueprint $table) {
            $table->string('id', length: 100)->change();
        });

        Schema::table('tiktok_event_webhooks', function (Blueprint $table) {
            $table->string('shop_id', length: 100)->change();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tiktok_shops', function (Blueprint $table) {
            //
        });
    }
};
