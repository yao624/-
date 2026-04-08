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
        Schema::table('clicks', function (Blueprint $table) {
            $table->index('fb_campaign_source_id');
            $table->index('fb_adset_source_id');
            $table->index('fb_ad_source_id');
        });

        Schema::table('conversions', function (Blueprint $table) {
            $table->index('fb_campaign_source_id');
            $table->index('fb_adset_source_id');
            $table->index('fb_ad_source_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clicks', function (Blueprint $table) {
            $table->dropIndex(['fb_campaign_source_id']);
            $table->dropIndex(['fb_adset_source_id']);
            $table->dropIndex(['fb_ad_source_id']);
        });

        Schema::table('conversions', function (Blueprint $table) {
            $table->dropIndex(['fb_campaign_source_id']);
            $table->dropIndex(['fb_adset_source_id']);
            $table->dropIndex(['fb_ad_source_id']);
        });
    }
};
