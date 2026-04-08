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
        Schema::table('cloudflares', function (Blueprint $table) {
            // 删除现有的自增 id 字段
            $table->dropColumn('id');
        });

        Schema::table('cloudflares', function (Blueprint $table) {
            // 添加新的 ulid 字段作为主键
            $table->ulid('id')->primary();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cloudflares', function (Blueprint $table) {
            // 删除 ulid 字段
            $table->dropColumn('id');
        });

        Schema::table('cloudflares', function (Blueprint $table) {
            // 添加回自增 id 字段
            $table->id();
        });
    }
};
