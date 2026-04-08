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
        Schema::table('fb_ads', function (Blueprint $table) {
            $table->boolean('auto_add_languages')->default(false)->comment('是否自动添加多语言，默认关闭');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_ads', function (Blueprint $table) {
            $table->dropColumn('auto_add_languages');
        });
    }
};
