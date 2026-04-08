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
        Schema::table('fraud_configs', function (Blueprint $table) {
            $table->json('excluded_ads')->nullable()->comment('排除检测的广告source_id列表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fraud_configs', function (Blueprint $table) {
            $table->dropColumn('excluded_ads');
        });
    }
};
