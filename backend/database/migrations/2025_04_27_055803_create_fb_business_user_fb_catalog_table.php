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
        Schema::create('fb_business_user_fb_catalog', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
            $table->softDeletes();

            $table->ulid('fb_business_user_id');
            $table->ulid('fb_catalog_id');
            $table->foreign('fb_business_user_id')->references('id')->on('fb_business_users')->onDelete('cascade');
            $table->foreign('fb_catalog_id')->references('id')->on('fb_catalogs')->onDelete('cascade');
            $table->string('role');
            $table->json('tasks'); // 添加 JSON 类型字段

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_business_user_fb_catalog');
    }
};
