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
        Schema::create('tags', function (Blueprint $table) {
            $table->ulid('id');
            $table->timestamps();
            $table->softDeletes();

            $table->string('name')->unique();
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->ulidMorphs('taggable');
            $table->string('tag_id', 26);
            $table->primary(['tag_id', 'taggable_id', 'taggable_type']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
    }
};
