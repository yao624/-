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
        Schema::create('ruleables', function (Blueprint $table) {
            $table->string('rule_id');
            $table->string('ruleable_id');
            $table->string('ruleable_type');
            $table->primary(['rule_id', 'ruleable_id', 'ruleable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ruleables');
    }
};
