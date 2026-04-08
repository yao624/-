<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('meta_material_groups')) {
            return;
        }

        Schema::create('meta_material_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('group_name', 100)->unique();
            $table->string('group_desc', 500)->nullable();
            $table->dateTime('create_time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_material_groups');
    }
};

