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
        Schema::create('cards', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();

            $table->softDeletes();

            $table->text('notes')->nullable();
            $table->string('name')->nullable();
            $table->string('source_id');
            $table->string('status');
            $table->float('balance');
            $table->string('number');
            $table->string('cvv');
            $table->string('expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
