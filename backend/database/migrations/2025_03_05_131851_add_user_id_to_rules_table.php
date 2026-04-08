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
        Schema::table('rules', function (Blueprint $table) {
            $table->ulid('user_id')->nullable(); // 添加 user_id 列
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null'); // 设置外键约束
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rules', function (Blueprint $table) {
            $table->dropForeign(['user_id']); // 移除外键约束
            $table->dropColumn('user_id'); // 删除 user_id 列
        });
    }
};
