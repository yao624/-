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
        Schema::table('finger_browser_groups', function (Blueprint $table) {
            $table->string("group_id");
            $table->string('group_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finger_browser_groups', function (Blueprint $table) {
            $table->dropColumn('group_id');
            $table->dropColumn('group_name');
        });
    }
};
