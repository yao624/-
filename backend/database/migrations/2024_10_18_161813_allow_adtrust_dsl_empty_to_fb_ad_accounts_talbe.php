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
        Schema::table('fb_ad_accounts', function (Blueprint $table) {
            $table->string('adtrust_dsl')->nullable()->change();
            $table->string('original_adtrust_dsl')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_ad_accounts', function (Blueprint $table) {
            $table->string('adtrust_dsl')->nullable(false)->change();
            $table->string('original_adtrust_dsl')->nullable(false)->change();
        });
    }
};
