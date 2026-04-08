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
        Schema::create('fb_page_posts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();

            $table->softDeletes();
            $table->text('notes')->nullable(true);

            $table->text('primary_text')->nullable();
            $table->string('headline')->nullable();
            $table->string('description')->nullable();
            $table->string('post_type')->nullable();
            $table->string('url')->nullable();
            $table->string('permalink_url')->nullable();
            $table->dateTime('created_time');
            $table->string('source_id');

            $table->string('campaign_source_id')->nullable();
            $table->string('adset_source_id')->nullable();
            $table->string('ad_source_id')->nullable();
            $table->string('page_source_id')->nullable();
            $table->string('ad_account_source_id')->nullable();

            $table->json('media')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_page_posts');
    }
};
