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
        Schema::create('ad_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();

            $table->softDeletes();
            $table->text('notes')->nullable(true);

            $table->ulid('user_id')->nullable();
            $table->ulid('fb_ad_account_id')->nullable();
            $table->ulid('fb_ad_template_id')->nullable();
            $table->ulid('fb_account_id')->nullable();
            $table->ulid('fb_api_token_id')->nullable();
            $table->ulid('fb_pixel_id')->nullable();
            $table->ulid('fb_page_id')->nullable();
            $table->ulid('fb_page_form_id')->nullable();
            $table->ulid('material_id')->nullable();
            $table->ulid('copywriting_id')->nullable();
            $table->ulid('link_id')->nullable();
            $table->string('operator_type')->nullable();
            $table->integer('launch_mode')->nullable();
            $table->string('post_source_id')->nullable();
            $table->string('is_success')->nullable();
            $table->text('failed_reason')->nullable();
//            $table->boolean('campaign_created')->nullable();
//            $table->text('campaign_failed_reason')->nullable();
//            $table->boolean('adset_created')->nullable();
//            $table->text('adset_failed_reason')->nullable();
//            $table->boolean('ad_created')->nullable();
//            $table->text('ad_failed_reason')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_logs');
    }
};
