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
        Schema::table('agents', function (Blueprint $table) {
            $table->string('name');
            $table->string('ip')->nullable();
            $table->string('port');
            $table->string('domain')->nullable();
            $table->string('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('ip');
            $table->dropColumn('port');
            $table->dropColumn('domain');
            $table->dropColumn('token');
        });
    }
};
