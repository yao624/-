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
        Schema::table('fb_page_forms', function (Blueprint $table) {
            $table->renameColumn('local', 'locale');
            $table->dropColumn('page_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_page_forms', function (Blueprint $table) {
            $table->renameColumn('locale', 'local');
            $table->string('page_id')->nullable();
        });
    }
};
