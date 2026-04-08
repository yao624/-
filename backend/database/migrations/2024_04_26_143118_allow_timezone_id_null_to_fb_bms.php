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
        Schema::table('fb_bms', function (Blueprint $table) {
            $table->string('timezone_id')->nullable()->change();
            $table->string('verification_status')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_bms', function (Blueprint $table) {
            $table->string('timezone_id')->nullable(false)->change();
            $table->string('verification_status')->nullable(false)->change();

        });
    }
};
