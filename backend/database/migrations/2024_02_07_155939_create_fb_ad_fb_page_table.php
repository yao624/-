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
        Schema::create('fb_ad_fb_page', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
            $table->ulid('fb_ad_id');
            $table->ulid('fb_page_id')->nullable()->comment('有可能这个主页不在我这边');

            $table->foreign('fb_ad_id')->references('id')->on('fb_ads')->onDelete('cascade');
            $table->foreign('fb_page_id')->references('id')->on('fb_pages')->onDelete('cascade');

            $table->string('fb_page_source_id')->nullable()->comment('记录Page I,如果没有找到的话');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_ad_fb_page');
    }
};
