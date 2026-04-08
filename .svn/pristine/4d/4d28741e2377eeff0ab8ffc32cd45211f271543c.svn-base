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
        Schema::table('fb_ad_accounts', function (Blueprint $table) {
            $table->string('source_id');
            $table->integer('adtrust_dsl');
            $table->string('account_status');
            $table->integer('account_status_code');
            $table->json('adspaymentcycle')->nullable();
            $table->float('age')->nullable();
            $table->string('total_spent');
            $table->string('balance');
            $table->string('amount_spent');
            $table->json('assigned_partners')->nullable();
            $table->json('business')->nullable();
            $table->string('spend_cap');
            $table->string('business_restriction_reason')->nullable();
            $table->timestamp('created_time');
            $table->string('currency');
            $table->json('current_unbilled_spend')->nullable();
            $table->string('disable_reason');
            $table->integer('disable_reason_code');
            $table->json('max_billing_threshold')->nullable();
            $table->string('name');
            $table->string('owner');
            $table->boolean('is_orignal');
            $table->string('timezone_id');
            $table->string('timezone_name');
            $table->boolean('enable_rule')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_ad_accounts', function (Blueprint $table) {
            $table->dropColumn('source_id');
            $table->dropColumn('adtrust_dsl');
            $table->dropColumn('account_status');
            $table->dropColumn('account_status_code');
            $table->dropColumn('adspaymentcycle');
            $table->dropColumn('age');
            $table->dropColumn('total_spent');
            $table->dropColumn('balance');
            $table->dropColumn('amount_spent');
            $table->dropColumn('assigned_partners');
            $table->dropColumn('business');
            $table->dropColumn('spend_cap');
            $table->dropColumn('business_restriction_reason');
            $table->dropColumn('created_time');
            $table->dropColumn('currency');
            $table->dropColumn('current_unbilled_spend');
            $table->dropColumn('disable_reason');
            $table->dropColumn('disable_reason_code');
            $table->dropColumn('max_billing_threshold');
            $table->dropColumn('name');
            $table->dropColumn('owner');
            $table->dropColumn('is_orignal');
            $table->dropColumn('timezone_id');
            $table->dropColumn('timezone_name');
            $table->dropColumn('enable_rule');
        });
    }
};
