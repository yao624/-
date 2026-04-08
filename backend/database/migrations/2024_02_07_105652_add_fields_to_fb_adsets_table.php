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
        Schema::table('fb_adsets', function (Blueprint $table) {
            $table->ulid('fb_campaign_id');
            $table->foreign('fb_campaign_id')->on('fb_campaigns')->references('id')->onDelete('cascade');
            $table->ulid('pixel_id')->nullable();
            $table->foreign('pixel_id')->on('fb_pixels')->references('id')->onDelete('cascade');
            $table->string('account_id');
            $table->string('billing_event');
            $table->string('budget_remaining')->nullable();
            $table->string('campaign_id');
            $table->string('configured_status');
            $table->timestamp('created_time');
            $table->string('daily_budget')->nullable();
            $table->string('lifetime_budget')->nullable();
            $table->string('effective_status');
            $table->string('source_id');
            $table->boolean('is_dynamic_creative');
            $table->string('name');
            $table->string('optimization_goal');
            $table->json('promoted_object');
            $table->string('source_adset_id')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->string('status');
            $table->json('targeting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_adsets', function (Blueprint $table) {
            $table->dropForeign(['fb_campaign_id']);
            $table->dropForeign(['pixel_id']);
            $table->dropColumn('fb_campaign_id');
            $table->dropColumn('pixel_id');
            $table->dropColumn('account_id');
            $table->dropColumn('billing_event');
            $table->dropColumn('budget_remaining');
            $table->dropColumn('campaign_id');
            $table->dropColumn('configured_status');
            $table->dropColumn('created_time');
            $table->dropColumn('daily_budget');
            $table->dropColumn('lifetime_budget');
            $table->dropColumn('effective_status');
            $table->dropColumn('source_id');
            $table->dropColumn('is_dynamic_creative');
            $table->dropColumn('name');
            $table->dropColumn('optimization_goal');
            $table->dropColumn('promoted_object');
            $table->dropColumn('source_adset_id');
            $table->dropColumn('start_time');
            $table->dropColumn('status');
            $table->dropColumn('targeting');
        });
    }
};
