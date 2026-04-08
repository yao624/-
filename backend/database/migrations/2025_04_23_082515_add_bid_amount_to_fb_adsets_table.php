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
        Schema::table('fb_adsets', function (Blueprint $table) {
            $table->float('bid_amount')->nullable();
            $table->float('original_bid_amount')->nullable();
            $table->string('bid_strategy')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_adsets', function (Blueprint $table) {
            $table->dropColumn(['bid_amount', 'original_bid_amount', 'bid_strategy']);
        });
    }
};
