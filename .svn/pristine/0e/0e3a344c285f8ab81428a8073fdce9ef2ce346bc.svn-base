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
        Schema::create('subid_mappings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
            $table->softDeletes();

            $table->string('name');
            $table->string('user_id');
            $table->string('subid_1');
            $table->string('subid_2');
            $table->string('subid_3');
            $table->string('subid_4');
            $table->string('subid_5');
            $table->string('fb_campaign_id')->nullable();
            $table->string('fb_adset_id')->nullable();
            $table->string('fb_ad_id')->nullable();

        });

        Schema::table('networks', function (Blueprint $table) {
            $table->ulid('subid_mapping_id')->nullable()->after('id');
            $table->foreign('subid_mapping_id')->references('id')->on('subid_mappings')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('networks', function (Blueprint $table) {
            $table->dropForeign(['subid_mapping_id']); // 移除外键约束
            $table->dropColumn('subid_mapping_id');    // 删除字段
        });
        Schema::dropIfExists('subid_mappings');

    }
};
