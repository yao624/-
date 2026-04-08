<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('copywritings', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('description')
                ->comment('多语言文案：{ "en_US": { "primary_text","headline","description" }, ... }');
        });
    }

    public function down(): void
    {
        Schema::table('copywritings', function (Blueprint $table) {
            $table->dropColumn('translations');
        });
    }
};
