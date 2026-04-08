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
            $table->json('countries_included')->nullable();
            $table->json('countries_excluded')->nullable();
            $table->json('regions_included')->nullable();
            $table->json('regions_excluded')->nullable();
            $table->json('cities_included')->nullable();
            $table->json('cities_excluded')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_ad_templates', function (Blueprint $table) {
            $table->dropColumn('countries_included');
            $table->dropColumn('countries_excluded');
            $table->dropColumn('regions_included');
            $table->dropColumn('regions_excluded');
            $table->dropColumn('cities_included');
            $table->dropColumn('cities_excluded');
        });
    }
};
