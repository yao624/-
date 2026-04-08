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
            // business_user 和 system_user
            $table->string('user_type')->nullable();

            // 是否用来创建广告的用户，system user
            $table->boolean('is_operator')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_business_users', function (Blueprint $table) {
            $table->dropColumn('user_type');
            $table->dropColumn('is_operator');

        });
    }
};
