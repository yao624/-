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
        Schema::create('cloudflares', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('notes')->nullable();
            $table->softDeletes();

            $table->string('email');
            $table->string('account_id')->nullable();
            $table->string('api_token')->nullable();
            $table->string('kv_namespace_id')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloudflares');
    }
};
