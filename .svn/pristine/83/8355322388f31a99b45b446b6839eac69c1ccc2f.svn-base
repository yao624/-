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
        Schema::create('fb_pixel_fb_bm', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
            $table->ulid('fb_pixel_id');
            $table->ulid('fb_bm_id');
            $table->foreign('fb_pixel_id')->on('fb_pixels')->references('id')->onDelete('cascade');
            $table->foreign('fb_bm_id')->on('fb_bms')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_pixel_fb_bm');
    }
};
