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
        Schema::create('card_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();

            $table->softDeletes();

            $table->text('notes')->nullable();
            $table->string('card_id');
            $table->string('status');
            $table->float('transaction_amount');
            $table->timestamp('transaction_date');
            $table->string('transaction_type');
            $table->string('merchant_name')->nullable();
            $table->string('custom_1')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_transactions');
    }
};
