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
        Schema::table('fb_ad_accounts', function (Blueprint $table) {
            $table->integer('original_adtrust_dsl');
            $table->string('original_balance');
            $table->string('original_amount_spent');
            $table->string('original_spend_cap');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_ad_accounts', function (Blueprint $table) {
            $table->dropColumn('original_adtrust_dsl');
            $table->dropColumn('original_balance');
            $table->dropColumn('original_amount_spent');
            $table->dropColumn('original_spend_cap');
        });
    }
};
