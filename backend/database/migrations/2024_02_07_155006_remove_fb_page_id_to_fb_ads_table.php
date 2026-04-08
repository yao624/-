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
        Schema::table('fb_ads', function (Blueprint $table) {
            $table->dropForeign(['fb_page_id']);
            $table->dropColumn('fb_page_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_ads', function (Blueprint $table) {
            $table->ulid('fb_page_id')->nullable();
            $table->foreign('fb_page_id')->on('fb_pages')->references('id')->onDelete('cascade');
        });
    }
};
