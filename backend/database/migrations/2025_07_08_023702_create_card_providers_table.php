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
        Schema::create('card_providers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name')->unique()->comment('内部使用的provider名称');
            $table->string('nick_name')->unique()->comment('前端显示的provider昵称');
            $table->json('config')->nullable()->comment('provider配置信息');
            $table->boolean('active')->default(true)->comment('是否启用');
            $table->text('notes')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_providers');
    }
};
