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
        Schema::create('fb_api_token_fb_page', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
            $table->softDeletes();
            $table->ulid('fb_api_token_id');
            $table->ulid('fb_page_id');
            $table->foreign('fb_api_token_id')->references('id')->on('fb_api_tokens')->onDelete('cascade');
            $table->foreign('fb_page_id')->references('id')->on('fb_pages')->onDelete('cascade');
            $table->json('tasks')->nullable(); // 添加 JSON 类型字段，允许为空
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_api_token_fb_page');
    }
};
