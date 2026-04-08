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
        Schema::table('fb_page_forms', function (Blueprint $table) {
            $table->text('privacy_policy_url')->change();
            $table->text('follow_up_action_url')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_page_forms', function (Blueprint $table) {
            $table->json('privacy_policy_url')->change();
            $table->string('follow_up_action_url')->change();
        });
    }
};
