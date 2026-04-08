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
        Schema::create('fb_account_page', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
            $table->softDeletes(); // 启用软删除

            $table->ulid('fb_account_id');
            $table->ulid('fb_page_id');
            $table->json('tasks');
            $table->string('role')->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreign('fb_account_id')->references('id')->on('fb_accounts')->onDelete('cascade');
            $table->foreign('fb_page_id')->references('id')->on('fb_pages')->onDelete('cascade');

            $table->unique(['fb_account_id', 'fb_page_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_account_page');
    }
};
