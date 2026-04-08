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
        Schema::create('fb_catalog_products', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
            $table->softDeletes();

            $table->string('source_id');
            $table->ulid('fb_catalog_id');
            $table->text('name');
            $table->text('description');
            $table->text('url');
            $table->text('image_url');
            $table->string('retailer_id');
            $table->string('currency');
            $table->string('price');
            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_catalog_products');
    }
};
