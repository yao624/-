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
        Schema::table('fb_accounts', function (Blueprint $table) {
//            $table->index('name', 'idx_fb_accounts_name');
//            $table->index('user_id', 'idx_fb_accounts_user_id');
//            $table->index('owner_id', 'idx_fb_accounts_owner_id');
        });

        Schema::table('fb_ad_accounts', function (Blueprint $table) {
            $table->index('source_id', 'idx_fb_ad_accounts_source_id');
            $table->index('name', 'idx_fb_ad_accounts_name');
        });

        Schema::table('fb_pages', function (Blueprint $table) {
            $table->index('source_id', 'idx_fb_pages_source_id');
            $table->index('name', 'idx_fb_pages_name');
        });

        Schema::table('fb_bms', function (Blueprint $table) {
            $table->index('source_id', 'idx_fb_bms_source_id');
            $table->index('name', 'idx_fb_bms_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_accounts', function (Blueprint $table) {
            $table->dropIndex('idx_fb_accounts_source_id');
            $table->dropIndex('idx_fb_accounts_name');
            $table->dropIndex('idx_fb_accounts_user_id');
            $table->dropIndex('idx_fb_accounts_owner_id');
        });

        Schema::table('fb_ad_accounts', function (Blueprint $table) {
            $table->dropIndex('idx_fb_ad_accounts_source_id');
            $table->dropIndex('idx_fb_ad_accounts_name');
        });

        Schema::table('fb_pages', function (Blueprint $table) {
            $table->dropIndex('idx_fb_pages_source_id');
            $table->dropIndex('idx_fb_pages_name');
        });

        Schema::table('fb_bms', function (Blueprint $table) {
            $table->dropIndex('idx_fb_bms_source_id');
            $table->dropIndex('idx_fb_bms_name');
        });
    }
};
