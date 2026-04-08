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
            $table->string('call_to_action')->nullable();
            $table->text('url_params')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_ad_templates', function (Blueprint $table) {
            $table->dropColumn(['call_to_action']);
            $table->dropColumn(['url_params']);
        });
    }
};
