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
        Schema::create('fb_bm_fb_catalog', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
            $table->softDeletes();

            $table->ulid('fb_bm_id');
            $table->ulid('fb_catalog_id');
            $table->foreign('fb_bm_id')->references('id')->on('fb_bms')->onDelete('cascade');
            $table->foreign('fb_catalog_id')->references('id')->on('fb_catalogs')->onDelete('cascade');
            $table->json('tasks')->nullable(); // 添加 JSON 类型字段，允许为空
            $table->string('relation');
            $table->string('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_bm_fb_catalog');
    }
};
