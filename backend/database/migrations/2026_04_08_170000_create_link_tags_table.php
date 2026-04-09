<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_tags', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('link_id');
            $table->string('user_id', 26)->index();
            $table->string('name', 191)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['link_id', 'user_id']);
            $table->index(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_tags');
    }
};
