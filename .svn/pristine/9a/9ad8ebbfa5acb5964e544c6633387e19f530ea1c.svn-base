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
            $table->string('source_id');
            $table->string('name');
            $table->string('created_time');
            $table->string('timezone_id');
            $table->string('two_factor_type')->nullable();
            $table->string('verification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_bms', function (Blueprint $table) {
            $table->dropColumn('source_id');
            $table->dropColumn('name');
            $table->dropColumn('created_time');
            $table->dropColumn('timezone_id');
            $table->dropColumn('two_factor_type');
            $table->dropColumn('verification_status');
        });
    }
};
