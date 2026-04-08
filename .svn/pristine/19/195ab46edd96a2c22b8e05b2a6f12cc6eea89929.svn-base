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
        Schema::create('request_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->index(); // 支持IPv6
            $table->text('user_agent')->nullable();
            $table->string('request_method', 10)->index();
            $table->string('request_path', 500)->index();
            $table->json('query_parameters')->nullable();
            $table->json('request_body')->nullable();
            $table->integer('response_status')->nullable()->index();
            $table->integer('response_time')->nullable(); // 响应时间（毫秒）
            $table->timestamp('requested_at')->index();
            $table->timestamps();
            $table->softDeletes();

            // 外键约束
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // 索引优化
            $table->index(['user_id', 'requested_at']);
            $table->index(['ip_address', 'requested_at']);
            $table->index(['request_method', 'request_path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_logs');
    }
};
