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
        Schema::create('cron_jobs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->softDeletes();
            $table->timestamps();
            $table->string('name')->nullable(false);
            $table->string('object_type')->nullable(false);
            $table->json('object_value')->nullable(false);
            $table->string('timezone')->nullable(false);
            $table->time('start_time')->nullable(true);
            $table->time('stop_time')->nullable(true);
            $table->string('user_id')->nullable(false);
            $table->boolean('active')->nullable(true);
            $table->text('notes')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cron_jobs');
    }
};
