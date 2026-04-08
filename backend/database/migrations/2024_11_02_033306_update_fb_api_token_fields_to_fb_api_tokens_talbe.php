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
        Schema::table('fb_api_tokens', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->string('bm_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_api_tokens', function (Blueprint $table) {
            $table->dropForeign(['bm_id']);
            $table->dropColumn(['bm_id']);
            $table->dropColumn(['name']);
        });
    }
};
