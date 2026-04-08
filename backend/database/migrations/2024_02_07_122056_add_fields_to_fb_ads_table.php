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
            $table->ulid('fb_campaign_id');
            $table->ulid('fb_adset_id');
            $table->ulid('fb_page_id')->nullable();

            $table->foreign('fb_campaign_id')->on('fb_campaigns')->references('id')->onDelete('cascade');
            $table->foreign('fb_adset_id')->on('fb_adsets')->references('id')->onDelete('cascade');
            $table->foreign('fb_page_id')->on('fb_pages')->references('id')->onDelete('cascade');

            $table->string('adset_id');
            $table->string('campaign_id');
            $table->string('configured_status');
            $table->timestamp('created_time');
            $table->json('creative');
            $table->string('effective_status');
            $table->string('source_id');
            $table->string('name');
            $table->string('preview_shareable_link')->nullable();
            $table->string('source_ad_id');
            $table->string('status');
            $table->string('post_url')->nullable();
            $table->timestamp('updated_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_ads', function (Blueprint $table) {

            $table->dropForeign(['fb_campaign_id']);
            $table->dropForeign(['fb_adset_id']);
            $table->dropForeign(['fb_page_id']);

            $table->dropColumn('fb_campaign_id');
            $table->dropColumn('fb_adset_id');
            $table->dropColumn('fb_page_id');
            $table->dropColumn('adset_id');
            $table->dropColumn('campaign_id');
            $table->dropColumn('configured_status');
            $table->dropColumn('created_time');
            $table->dropColumn('creative');
            $table->dropColumn('effective_status');
            $table->dropColumn('source_id');
            $table->dropColumn('name');
            $table->dropColumn('preview_shareable_link');
            $table->dropColumn('source_ad_id');
            $table->dropColumn('status');
            $table->dropColumn('post_url');
            $table->dropColumn('updated_time');
        });
    }
};
