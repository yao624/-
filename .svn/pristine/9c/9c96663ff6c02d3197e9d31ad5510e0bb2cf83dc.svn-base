<?php

use Carbon\Carbon;
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
        // 创建一个新的 timestamp 字段
        Schema::table('fb_bms', function (Blueprint $table) {
            $table->timestamp('new_created_time')->nullable();
        });

        // 将旧数据转换为 timestamp 并保存到新的字段中
        DB::table('fb_bms')->get()->each(function ($item) {
            $date = Carbon::parse($item->created_time);
            DB::table('fb_bms')
                ->where('id', $item->id)
                ->update(['new_created_time' => $date]);
        });

        // 删除旧的字段
        Schema::table('fb_bms', function (Blueprint $table) {
            $table->dropColumn('created_time');
        });

        // 将新的字段重命名为 'created_time'
        Schema::table('fb_bms', function (Blueprint $table) {
            $table->renameColumn('new_created_time', 'created_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_bms', function (Blueprint $table) {
            $table->string('created_time')->change();
        });
    }
};
