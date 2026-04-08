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
        // 向 materials 表添加 user_id 字段
        Schema::table('materials', function (Blueprint $table) {
            $table->ulid('user_id')->after('id')->nullable(); // 选择nullable，具体根据需求
        });

        // 向 copywritings 表添加 user_id 字段
        Schema::table('copywritings', function (Blueprint $table) {
            $table->ulid('user_id')->after('id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });

        Schema::table('copywritings', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
