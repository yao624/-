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
        Schema::create('fb_account_fb_ad_account', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
            $table->ulid('fb_ad_account_id');
            $table->ulid('fb_account_id')->nullable();
            $table->string('source_id');
            $table->string('relation')->nullable();

            $table->foreign('fb_ad_account_id')->references('id')->on('fb_ad_accounts')->onDelete('cascade');
            $table->foreign('fb_account_id')->references('id')->on('fb_accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_account_fb_ad_account');
    }
};
