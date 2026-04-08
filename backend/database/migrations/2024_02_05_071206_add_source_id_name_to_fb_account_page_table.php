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
        Schema::table('fb_account_page', function (Blueprint $table) {
            $table->ulid('fb_account_id')->nullable()->change();
            $table->string('source_id')->nullable();
            $table->string('name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_account_page', function (Blueprint $table) {
            $table->ulid('fb_account_id')->nullable(false)->change();
            $table->dropColumn('source_id');
            $table->dropColumn('name');
        });
    }
};
