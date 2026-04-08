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
        Schema::create('fb_api_token_fb_ad_account', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();

            $table->ulid('fb_api_token_id');
            $table->ulid('fb_ad_account_id');

            $table->foreign('fb_api_token_id')->on('fb_api_tokens')->references('id')->onDelete('cascade');
            $table->foreign('fb_ad_account_id')->on('fb_ad_accounts')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_api_token_fb_ad_account');
    }
};
