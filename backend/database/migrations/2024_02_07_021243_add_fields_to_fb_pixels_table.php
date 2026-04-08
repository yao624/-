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
        Schema::table('fb_pixels', function (Blueprint $table) {
            $table->string('name');
            $table->string('pixel');
            $table->boolean('is_created_by_business');
            $table->boolean('is_unavailable');
            $table->json('owner_business')->nullable();
            $table->json('creator')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_pixels', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('pixel');
            $table->dropColumn('is_created_by_business');
            $table->dropColumn('is_unavailable');
            $table->dropColumn('owner_business');
            $table->dropColumn('creator');
        });
    }
};
