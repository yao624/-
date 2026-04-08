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
        Schema::table('fb_ad_templates', function (Blueprint $table) {
            $table->json('locales')->nullable();
            $table->json('interests')->nullable();
            $table->json('publisher_platforms')->nullable();
            $table->json('device_platforms')->nullable();
            $table->boolean('wireless_carrier')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_ad_templates', function (Blueprint $table) {
            $table->dropColumn(['locales']);
            $table->dropColumn(['interests']);
            $table->dropColumn(['publisher_platforms']);
            $table->dropColumn(['device_platforms']);
            $table->dropColumn(['wireless_carrier']);
        });
    }
};
