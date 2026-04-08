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
        Schema::create('fb_product_fb_product_set', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
            $table->softDeletes();

            $table->ulid('fb_catalog_product_id');
            $table->ulid('fb_catalog_product_set_id');
            $table->foreign('fb_catalog_product_id')->references('id')
                ->on('fb_catalog_products')->onDelete('cascade');
            $table->foreign('fb_catalog_product_set_id')->references('id')
                ->on('fb_catalog_product_sets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_product_fb_product_set');
    }
};
