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
        Schema::table('fb_adset_insights', function (Blueprint $table) {
            $table->string('account_currency');
            $table->string('account_id');
            $table->string('account_name');
            $table->json('actions')->nullable();
            $table->json('action_values')->nullable();
            $table->bigInteger('clicks')->nullable();
            $table->json('cost_per_action_type')->nullable();
            $table->float('cost_per_inline_link_click')->nullable();
            $table->float('cpc')->nullable();
            $table->float('cpm')->nullable();
            $table->float('ctr')->nullable();
            $table->date('date_start');
            $table->date('date_stop');
            $table->float('frequency')->nullable();
            $table->bigInteger('impressions')->nullable();
            $table->float('inline_link_click_ctr')->nullable();
            $table->bigInteger('inline_link_clicks')->nullable();
            $table->string('objective')->nullable();
            $table->json('purchase_roas')->nullable();
            $table->float('purchase_roas_value')->nullable();
            $table->string('quality_ranking')->nullable();
            $table->bigInteger('reach');
            $table->float('spend')->nullable();

            $table->string('campaign_id');
            $table->string('campaign_name')->nullable();
            $table->string('adset_id');
            $table->string('adset_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_adset_insights', function (Blueprint $table) {
            $table->dropColumn('account_currency');
            $table->dropColumn('account_id');
            $table->dropColumn('account_name');
            $table->dropColumn('actions');
            $table->dropColumn('action_values');
            $table->dropColumn('clicks');
            $table->dropColumn('cost_per_action_type');
            $table->dropColumn('cost_per_inline_link_click');
            $table->dropColumn('cpc');
            $table->dropColumn('cpm');
            $table->dropColumn('ctr');
            $table->dropColumn('date_start');
            $table->dropColumn('date_stop');
            $table->dropColumn('frequency');
            $table->dropColumn('impressions');
            $table->dropColumn('inline_link_click_ctr');
            $table->dropColumn('inline_link_clicks');
            $table->dropColumn('objective');
            $table->dropColumn('purchase_roas');
            $table->dropColumn('purchase_roas_value');
            $table->dropColumn('quality_ranking');
            $table->dropColumn('reach');
            $table->dropColumn('spend');

            $table->dropColumn('campaign_id');
            $table->dropColumn('campaign_name');
            $table->dropColumn('adset_id');
            $table->dropColumn('adset_name');
        });
    }
};
