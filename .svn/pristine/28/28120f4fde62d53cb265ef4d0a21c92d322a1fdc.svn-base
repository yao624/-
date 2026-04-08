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
        Schema::create('fb_account_insights', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
            $table->softDeletes();

            $table->text('notes')->nullable();
            $table->string('account_currency');
            $table->string('account_id');
            $table->string('account_name');
            $table->json('actions')->nullable();
            $table->json('action_values')->nullable();
            $table->bigInteger('clicks')->nullable();
            $table->json('cost_per_action_type')->nullable();
            $table->float('cost_per_inline_link_click')->nullable();
            $table->float('cpc')->nullable();
            $table->float('cpm')->nullable();
            $table->float('ctr')->nullable();
            $table->date('date_start');
            $table->date('date_stop');
            $table->float('frequency')->nullable();
            $table->bigInteger('impressions')->nullable();
            $table->float('inline_link_click_ctr')->nullable();
            $table->bigInteger('inline_link_clicks')->nullable();
            $table->float('objective');
            $table->json('purchase_roas')->nullable();
            $table->float('purchase_roas_value')->nullable();
            $table->string('quality_ranking')->nullable();
            $table->bigInteger('reach');
            $table->float('spend')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_account_insights');
    }
};
