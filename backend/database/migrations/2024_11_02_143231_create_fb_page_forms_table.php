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
        Schema::create('fb_page_forms', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();

            $table->softDeletes();
            $table->text('notes')->nullable(true);

            $table->string('source_id');
            $table->string('local');
            $table->string('name');
            $table->string('status');
            $table->dateTime('created_time');
            $table->json('thank_you_page')->nullable();
            $table->json('privacy_policy_url')->nullable();
            $table->json('legal_content')->nullable();
            $table->string('follow_up_action_url')->nullable();
            $table->string('leads_count');
            $table->string('page_source_id');
            $table->string('page_name');
            $table->string('page_id')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_page_forms');
    }
};
