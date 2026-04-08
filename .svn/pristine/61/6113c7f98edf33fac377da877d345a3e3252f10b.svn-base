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
        Schema::table('fb_api_tokens', function (Blueprint $table) {
            $table->string('app')->nullable()->comment('关联的 FbApp 的 source_id');
            $table->index('app');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_api_tokens', function (Blueprint $table) {
            $table->dropIndex(['app']);
            $table->dropColumn('app');
        });
    }
};
