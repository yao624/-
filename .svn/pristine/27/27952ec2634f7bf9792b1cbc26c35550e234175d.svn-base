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
            $table->string('transaction_id')->nullable();
            $table->string('click_datetime')->nullable();
            $table->string('network_id')->nullable();
            $table->string('offer_source_id')->nullable();
            $table->string('offer_source_name')->nullable();
            $table->string('sub_1')->nullable();
            $table->string('sub_2')->nullable();
            $table->string('sub_3')->nullable();
            $table->string('sub_4')->nullable();
            $table->string('sub_5')->nullable();
            $table->string('ip')->nullable();
            $table->string('fb_campaign_source_id')->nullable();
            $table->string('fb_adset_source_id')->nullable();
            $table->string('fb_ad_source_id')->nullable();
            $table->string('fb_pixel_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clicks', function (Blueprint $table) {
            $table->dropColumn('transaction_id');
            $table->dropColumn('click_datetime');
            $table->dropColumn('network_id');
            $table->dropColumn('offer_source_id');
            $table->dropColumn('offer_source_name');
            $table->dropColumn('sub_1');
            $table->dropColumn('sub_2');
            $table->dropColumn('sub_3');
            $table->dropColumn('sub_4');
            $table->dropColumn('sub_5');
            $table->dropColumn('ip');
            $table->dropColumn('fb_campaign_source_id');
            $table->dropColumn('fb_adset_source_id');
            $table->dropColumn('fb_ad_source_id');
            $table->dropColumn('fb_pixel_number');
        });
    }
};
