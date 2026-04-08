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
        Schema::create('fb_business_user_fb_page', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();

            $table->string('role');
            $table->json('tasks');
            $table->ulid('fb_page_id');
            $table->ulid('fb_business_user_id');

            $table->foreign('fb_page_id')->on('fb_pages')->references('id')->onDelete('cascade');
            $table->foreign('fb_business_user_id')->on('fb_business_users')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_business_user_fb_page');
    }
};
