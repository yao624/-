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
        Schema::create('fb_app_fb_ad_account', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
            $table->softDeletes();

            $table->ulid('fb_app_id');
            $table->ulid('fb_ad_account_id');

            $table->foreign('fb_app_id')->references('id')->on('fb_apps')->onDelete('cascade');
            $table->foreign('fb_ad_account_id')->references('id')->on('fb_ad_accounts')->onDelete('cascade');

            $table->unique(['fb_app_id', 'fb_ad_account_id'], 'fb_app_fb_ad_account_unique');
            $table->index('fb_app_id');
            $table->index('fb_ad_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_app_fb_ad_account');
    }
};
