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
            $table->float('original_cost_per_purchase')->nullable();
            $table->float('original_cost_per_lead')->nullable();
            $table->float('original_cost_to_add_to_cart')->nullable();
            $table->float('original_spend')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_ad_account_insights', function (Blueprint $table) {
            $table->dropColumn('original_cost_per_purchase');
            $table->dropColumn('original_cost_per_lead');
            $table->dropColumn('original_cost_to_add_to_cart');
            $table->dropColumn('original_spend');
        });
    }
};
