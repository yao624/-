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
        Schema::table('tracker_offer_clicks', function (Blueprint $table) {
            $table->timestamp('click_date');
            $table->string('offer', 400)->nullable();
            $table->string('landing', 400)->nullable();
            $table->string('country_flag')->nullable();
            $table->string('network_identifier')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracker_offer_clicks', function (Blueprint $table) {
            $table->dropColumn(['click_date']);
            $table->dropColumn(['offer']);
            $table->dropColumn(['landing']);
            $table->dropColumn(['country_flag']);
            $table->dropColumn(['network_identifier']);
        });
    }
};
