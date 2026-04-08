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
        Schema::table('fb_campaign_insights', function (Blueprint $table) {
            $table->index('campaign_id');
        });

        Schema::table('fb_adsets', function (Blueprint $table) {
            $table->index('source_id');
            $table->index('account_id');
        });
        Schema::table('fb_adset_insights', function (Blueprint $table) {
            $table->index('adset_id');
            $table->index('date_start');
        });

        Schema::table('fb_ad_insights', function (Blueprint $table) {
            $table->index('ad_id');
            $table->index('date_start');
        });

        Schema::table('clicks', function (Blueprint $table) {
            $table->index('click_datetime');
        });

        Schema::table('conversions', function (Blueprint $table) {
            $table->index('conversion_datetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_adsets', function (Blueprint $table) {
            $table->dropIndex(['source_id']);
            $table->dropIndex(['account_id']);
        });
        Schema::table('fb_adset_insights', function (Blueprint $table) {
            $table->dropIndex(['adset_id']);
            $table->dropIndex(['date_start']);
        });

        Schema::table('fb_ad_insights', function (Blueprint $table) {
            $table->dropIndex(['ad_id']);
            $table->dropIndex(['date_start']);
        });

        Schema::table('clicks', function (Blueprint $table) {
            $table->dropIndex(['click_datetime']);
        });

        Schema::table('conversions', function (Blueprint $table) {
            $table->dropIndex(['conversion_datetime']);
        });

        Schema::table('fb_campaign_insights', function (Blueprint $table) {
            $table->dropIndex(['campaign_id']);
        });
    }
};
