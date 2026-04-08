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
        Schema::table('fb_accounts', function (Blueprint $table) {
            $table->foreignUlid('owner_id')->nullable()->constrained('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_accounts', function (Blueprint $table) {
            $table->dropForeign(['owner_id']); // 先移除外键约束
            $table->dropColumn('owner_id'); // 然后移除列
        });
    }
};
