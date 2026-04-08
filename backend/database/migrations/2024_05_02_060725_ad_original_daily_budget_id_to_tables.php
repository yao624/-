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
            $table->string('lifetime_budget')->nullable();
            $table->string('original_daily_budget')->nullable();
            $table->string('original_lifetime_budget')->nullable();
        });

        Schema::table('fb_adsets', function (Blueprint $table) {
            $table->string('original_daily_budget')->nullable();
            $table->string('original_lifetime_budget')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_campaigns', function (Blueprint $table) {
            $table->dropColumn('lifetime_budget');
            $table->dropColumn('original_daily_budget');
            $table->dropColumn('original_lifetime_budget');
        });

        Schema::table('fb_adsets', function (Blueprint $table) {
            $table->dropColumn('original_daily_budget');
            $table->dropColumn('original_lifetime_budget');
        });
    }
};
