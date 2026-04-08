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
        Schema::table('finger_browsers', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->string('created_time');
            $table->string('user_id');
            $table->string('serial_number');
            $table->string('group_id')->nullable();
            $table->string('provider')->default('adspower');
            $table->foreignUlid('proxy_id')->nullable()->constrained()->onDelete('set null');
            $table->foreign('group_id')->references('group_id')->on('finger_browser_groups')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finger_browsers', function (Blueprint $table) {

            $table->dropForeign(['proxy_id']);
            $table->dropForeign(['group_id']);

            $table->dropColumn('name');
            $table->dropColumn('created_time');
            $table->dropColumn('proxy_id');
            $table->dropColumn('user_id');
            $table->dropColumn('serial_number');
            $table->dropColumn('provider');
            $table->dropColumn('proxy_id');
            $table->dropColumn('group_id');

        });
    }
};
