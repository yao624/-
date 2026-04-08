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
        Schema::table('fb_ad_account_insights', function (Blueprint $table) {
            $table->bigInteger('reach')->nullable()->change();
        });
        Schema::table('fb_ad_insights', function (Blueprint $table) {
            $table->bigInteger('reach')->nullable()->change();
        });
        Schema::table('fb_campaign_insights', function (Blueprint $table) {
            $table->bigInteger('reach')->nullable()->change();
        });
        Schema::table('fb_ad_insights', function (Blueprint $table) {
            $table->bigInteger('reach')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_ad_account_insights', function (Blueprint $table) {
            $table->bigInteger('reach')->nullable(false)->change();
        });
        Schema::table('fb_ad_insights', function (Blueprint $table) {
            $table->bigInteger('reach')->nullable(false)->change();
        });
        Schema::table('fb_campaign_insights', function (Blueprint $table) {
            $table->bigInteger('reach')->nullable(false)->change();
        });
        Schema::table('fb_ad_insights', function (Blueprint $table) {
            $table->bigInteger('reach')->nullable(false)->change();
        });
    }
};
