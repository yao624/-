<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 检查 links 表的 id 列是否已索引
        if (Schema::hasTable('links') && Schema::hasColumn('links', 'id')) {
            // 获取索引信息
            $indexes = DB::select('SHOW INDEXES FROM links WHERE Column_name = ?', ['id']);
            $indexExists = !empty($indexes); // 检查返回结果

            // 如果没有索引，添加索引
            if (!$indexExists) {
                Schema::table('links', function (Blueprint $table) {
                    $table->index('id'); // 添加索引
                });
            }
        }

        Schema::create('link_shares', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
            $table->softDeletes();

            $table->ulid('link_id');
            $table->ulid('user_id');

            $table->foreign('link_id')->references('id')->on('links')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('link_shares');
    }
};
