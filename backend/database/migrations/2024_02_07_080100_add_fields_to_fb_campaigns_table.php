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
        Schema::table('fb_campaigns', function (Blueprint $table) {
            $table->ulid('fb_ad_account_id');
            $table->foreign('fb_ad_account_id')->on('fb_ad_accounts')->references('id')->onDelete('cascade');
            $table->string('account_id');
            $table->string('bid_strategy');
            $table->string('budget_remaining')->nullable();
            $table->string('configured_status')->nullable();
            $table->timestamp('created_time')->nullable();
            $table->string('daily_budget')->nullable();
            $table->string('effective_status')->nullable();
            $table->string('source_id')->nullable();
            $table->string('name');
            $table->string('objective')->nullable();
            $table->string('source_campaign_id')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('updated_time')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_campaigns', function (Blueprint $table) {
            $table->dropForeign(['fb_ad_account_id']);
            $table->dropColumn('fb_ad_account_id');
            $table->dropColumn('account_id');
            $table->dropColumn('bid_strategy');
            $table->dropColumn('budget_remaining');
            $table->dropColumn('configured_status');
            $table->dropColumn('created_time');
            $table->dropColumn('daily_budget');
            $table->dropColumn('effective_status');
            $table->dropColumn('source_id');
            $table->dropColumn('name');
            $table->dropColumn('objective');
            $table->dropColumn('source_campaign_id');
            $table->dropColumn('start_time');
            $table->dropColumn('status');
            $table->dropColumn('updated_time');
        });
    }
};
