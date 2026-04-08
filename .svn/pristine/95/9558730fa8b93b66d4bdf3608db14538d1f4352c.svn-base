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
        Schema::create('fb_ad_account_fb_bm', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
            $table->ulid('fb_ad_account_id')->nullable();
            $table->ulid('fb_bm_id');
            $table->string('relation')->nullable()->comment('记录是 owner 还是 partner 的关系, 可能的值是 Owner, Partner');

            $table->foreign('fb_ad_account_id')->references('id')->on('fb_ad_accounts')->onDelete('set null');
            $table->foreign('fb_bm_id')->references('id')->on('fb_bms')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_ad_account_fb_bm');
    }
};
