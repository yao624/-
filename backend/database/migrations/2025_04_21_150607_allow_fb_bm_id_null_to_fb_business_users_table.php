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
//            $table->string('fb_bm_id')->nullable()->change();
            $table->foreignUlid('fb_bm_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_business_users', function (Blueprint $table) {
            $table->foreignUlid('fb_bm_id')->nullable(false)->change();
        });
    }
};
