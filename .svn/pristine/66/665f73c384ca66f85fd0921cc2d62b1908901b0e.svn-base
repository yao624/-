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
            $table->float('adtrust_dsl')->change();
            $table->float('original_adtrust_dsl')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_ad_accounts', function (Blueprint $table) {
            $table->integer('adtrust_dsl')->change();
            $table->integer('original_adtrust_dsl')->change();
        });
    }
};
