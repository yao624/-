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
        Schema::create('networks', function (Blueprint $table) {
            $table->ulid('id');
            $table->timestamps();
            $table->softDeletes();

            $table->string('name')->unique();
            $table->string('system_type')->comment('系统类型 Cake, Everflow, Jumb');
            $table->string('aff_id');
            $table->string('endpoint');
            $table->string('apikey');
            $table->string('click_placeholder');
            $table->text('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('networks');
    }
};
