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
        Schema::create('fb_ad_account_user', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
            $table->softDeletes();
            $table->ulid('user_id'); // Assuming 'users' use ULID
            $table->ulid('fb_ad_account_id'); //
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('fb_ad_account_id')->references('id')->on('fb_ad_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_ad_account_user');
    }
};
