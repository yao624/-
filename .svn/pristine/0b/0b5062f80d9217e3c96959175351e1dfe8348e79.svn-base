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
        Schema::create('fb_app_fb_bm', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
            $table->softDeletes();

            $table->ulid('fb_app_id');
            $table->ulid('fb_bm_id');

            $table->foreign('fb_app_id')->references('id')->on('fb_apps')->onDelete('cascade');
            $table->foreign('fb_bm_id')->references('id')->on('fb_bms')->onDelete('cascade');

            $table->enum('relation', ['owner', 'client'])->comment('BM与App的关系：owner表示拥有，client表示客户端关联');

            $table->unique(['fb_app_id', 'fb_bm_id', 'relation'], 'fb_app_fb_bm_unique');
            $table->index(['fb_app_id', 'relation']);
            $table->index(['fb_bm_id', 'relation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_app_fb_bm');
    }
};
