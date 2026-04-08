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
            $table->boolean('is_disabled_for_integrity_reasons')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_bms', function (Blueprint $table) {
            $table->dropColumn('is_disabled_for_integrity_reasons');
        });
    }
};
