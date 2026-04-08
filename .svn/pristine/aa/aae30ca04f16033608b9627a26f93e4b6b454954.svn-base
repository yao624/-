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
            $table->text('campaign_name');
            $table->text('adset_name');
            $table->text('ad_name');
            $table->string('bid_strategy');
            $table->string('bid_amount')->nullable();
            $table->string('budget_level');
            $table->string('budget_type');
            $table->string('budget');
            $table->string('objective');
            $table->string('accelerated');
            $table->string('conversion_location');
            $table->string('optimization_goal');
            $table->string('pixel_event')->nullable();
            $table->boolean('advantage_plus_audience')->nullable();
            $table->integer('genders')->nullable();
            $table->integer('age_min');
            $table->integer('age_max');
            $table->text('primary_text')->nullable();
            $table->text('headline_text')->nullable();
            $table->text('description_text')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_ad_templates', function (Blueprint $table) {
            $table->dropColumn(['campaign_name']);
            $table->dropColumn(['adset_name']);
            $table->dropColumn(['ad_name']);
            $table->dropColumn(['bid_strategy']);
            $table->dropColumn(['bid_amount']);
            $table->dropColumn(['budget_level']);
            $table->dropColumn(['budget_type']);
            $table->dropColumn(['budget']);
            $table->dropColumn(['objective']);
            $table->dropColumn(['accelerated']);
            $table->dropColumn(['conversion_location']);
            $table->dropColumn(['optimization_goal']);
            $table->dropColumn(['pixel_event']);
            $table->dropColumn(['advantage_plus_audience']);
            $table->dropColumn(['genders']);
            $table->dropColumn(['age_min']);
            $table->dropColumn(['age_max']);
            $table->dropColumn('primary_text');
            $table->dropColumn('headline_text');
            $table->dropColumn('description_text');
        });
    }
};
