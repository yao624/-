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
        Schema::create('card_bins', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('card_provider_id');
            $table->string('card_bin', 10)->comment('卡号前缀，6-10位');
            $table->string('card_type')->default('virtual')->comment('卡类型');
            $table->boolean('active')->default(true)->comment('是否启用');
            $table->text('notes')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();

            // 外键约束
            $table->foreign('card_provider_id')->references('id')->on('card_providers')->onDelete('cascade');

            // 联合唯一索引
            $table->unique(['card_provider_id', 'card_bin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_bins');
    }
};
