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
        Schema::table('fb_ad_account_fb_business_user', function (Blueprint $table) {
            $table->ulid('fb_ad_account_id');
            $table->ulid('fb_business_user_id');

            $table->foreign('fb_ad_account_id')->on('fb_ad_accounts')->references('id')->onDelete('cascade');
            $table->foreign('fb_business_user_id')->on('fb_business_users')->references('id')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_ad_account_fb_business_user', function (Blueprint $table) {
            $table->dropForeign(['fb_ad_account_id']);
            $table->dropForeign(['fb_business_user_id']);

            $table->dropColumn('fb_ad_account_id');
            $table->dropColumn('fb_business_user_id');
        });
    }
};
