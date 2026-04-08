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
        Schema::create('card_fb_ad_account', function (Blueprint $table) {
            $table->id();
            $table->ulid('card_id');
            $table->ulid('fb_ad_account_id');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            // 外键约束
            $table->foreign('card_id')->references('id')->on('cards')->onDelete('cascade');
            $table->foreign('fb_ad_account_id')->references('id')->on('fb_ad_accounts')->onDelete('cascade');

            // 唯一约束：一个卡片和一个广告账户的组合是唯一的
            $table->unique(['card_id', 'fb_ad_account_id']);

            // 索引
            $table->index('card_id');
            $table->index('fb_ad_account_id');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_fb_ad_account');
    }
};
