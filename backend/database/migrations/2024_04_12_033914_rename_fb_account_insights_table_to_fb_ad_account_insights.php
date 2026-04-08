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
        Schema::rename('fb_account_insights', 'fb_ad_account_insights');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('fb_ad_account_insights', 'fb_account_insights');
    }
};
