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
        Schema::table('fb_pages', function (Blueprint $table) {
            $table->integer('fan_count');
            $table->string('name');
            $table->boolean('promotion_eligible');
            $table->string('verification_status');
            $table->text('picture');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_pages', function (Blueprint $table) {
            $table->dropColumn('fan_count');
            $table->dropColumn('name');
            $table->dropColumn('promotion_eligible');
            $table->dropColumn('verification_status');
            $table->dropColumn('picture');
        });
    }
};
