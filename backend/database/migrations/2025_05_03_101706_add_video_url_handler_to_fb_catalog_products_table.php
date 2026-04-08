<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fb_catalog_products', function (Blueprint $table) {
            $table->text('video_url')->nullable();
            $table->text('video_handler')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_catalog_products', function (Blueprint $table) {
            $table->dropColumn('video_url');
            $table->dropColumn('video_handler');
        });
    }
};
