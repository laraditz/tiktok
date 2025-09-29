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

        Schema::table('tiktok_shops', function (Blueprint $table) {
            $table->dropColumn('identifier');
        });

        Schema::table('tiktok_access_tokens', function (Blueprint $table) {
            $table->string('subjectable_id', 100)->nullable()->change();
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
