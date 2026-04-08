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
            $table->string('default_funding')->nullable();
            $table->integer('funding_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_ad_accounts', function (Blueprint $table) {
            $table->dropColumn('default_funding');
            $table->dropColumn('funding_type');
        });
    }
};
