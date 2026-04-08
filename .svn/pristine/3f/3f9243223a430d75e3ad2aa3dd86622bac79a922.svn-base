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
        Schema::table('proxies', function (Blueprint $table) {
            $table->string('protocol')->default('http');
            $table->string('host');
            $table->integer('port');
            $table->string('username')->nullable();
            $table->string('password')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proxies', function (Blueprint $table) {
            $table->dropColumn('protocol');
            $table->dropColumn('host');
            $table->dropColumn('port');
            $table->dropColumn('username');
            $table->dropColumn('password');
        });
    }
};
