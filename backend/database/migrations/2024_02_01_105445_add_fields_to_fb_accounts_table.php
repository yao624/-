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
            $table->string('source_id')->nullable();
            $table->string('name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('gender')->nullable();
            $table->string('picture')->nullable();
            $table->string('twofa_key')->nullable();
            $table->text('cookies')->nullable();
            $table->string('token')->nullable();
            $table->boolean('token_valid')->default(false)->nullable();
            $table->string('useragent')->nullable();

            $table->foreignUlid('fingerbrowser_id')->nullable()->constrained('finger_browsers')->onDelete('set null');
            $table->foreignUlid('proxy_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_accounts', function (Blueprint $table) {
            $table->dropForeign(['fingerbrowser_id']);
            $table->dropForeign(['proxy_id']);

            $table->dropColumn('fingerbrowser_id');
            $table->dropColumn('proxy_id');
            $table->dropColumn('source_id');
            $table->dropColumn('name');
            $table->dropColumn('first_name');
            $table->dropColumn('last_name');
            $table->dropColumn('username');
            $table->dropColumn('password');
            $table->dropColumn('gender');
            $table->dropColumn('picture');
            $table->dropColumn('twofa_key');
            $table->dropColumn('cookies');
            $table->dropColumn('token');
            $table->dropColumn('token_valid');
            $table->dropColumn('useragent');
        });
    }
};
