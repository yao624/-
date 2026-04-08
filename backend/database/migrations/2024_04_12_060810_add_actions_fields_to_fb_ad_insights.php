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
        Schema::table('fb_ad_insights', function (Blueprint $table) {
            $table->integer('purchase')->nullable();
            $table->float('purchase_value')->nullable();
            $table->float('cost_per_purchase')->nullable();
            $table->integer('lead')->nullable();
            $table->float('cost_per_lead')->nullable();
            $table->integer('add_to_cart')->nullable();
            $table->float('cost_to_add_to_cart')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_ad_insights', function (Blueprint $table) {
            $table->dropColumn('purchase');
            $table->dropColumn('purchase_value');
            $table->dropColumn('cost_per_purchase');
            $table->dropColumn('lead');
            $table->dropColumn('cost_per_lead');
            $table->dropColumn('add_to_cart');
            $table->dropColumn('cost_to_add_to_cart');
        });
    }
};
