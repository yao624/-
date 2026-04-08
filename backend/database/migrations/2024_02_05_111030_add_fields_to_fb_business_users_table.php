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
        Schema::table('fb_business_users', function (Blueprint $table) {
            $table->string('source_id');
            $table->string('email');
            $table->string('finance_permission')->nullable();
            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('role');
            $table->string('two_fac_status');
            $table->string('expiry_time')->nullable();

            $table->foreignUlid('fb_account_id')->nullable()->references('id')->on('fb_accounts')->onDelete('cascade');
            $table->foreignUlid('fb_bm_id')->references('id')->on('fb_bms')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_business_users', function (Blueprint $table) {
            $table->dropForeign('fb_account_id');
            $table->dropForeign('fb_bm_id');

            $table->dropColumn('fb_account_id');
            $table->dropColumn('fb_bm_id');
            $table->dropColumn('source_id');
            $table->dropColumn('email');
            $table->dropColumn('finance_permission');
            $table->dropColumn('name');
            $table->dropColumn('first_name');
            $table->dropColumn('last_name');
            $table->dropColumn('role');
            $table->dropColumn('two_fac_status');
            $table->dropColumn('expiry_time');
        });
    }
};
