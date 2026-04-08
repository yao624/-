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
        Schema::table('fb_api_token_fb_ad_account', function (Blueprint $table) {
            $table->json('tasks')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_api_token_fb_ad_account', function (Blueprint $table) {
            $table->dropColumn(['tasks']);
        });
    }
};
