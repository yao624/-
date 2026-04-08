<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('meta_copy_items')) {
            return;
        }
        if (Schema::hasColumn('meta_copy_items', 'translations')) {
            return;
        }

        Schema::table('meta_copy_items', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('description')
                ->comment('多语言文案：{ "en_US": { "primary_text","headline","description" }, ... }');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('meta_copy_items')) {
            return;
        }
        if (! Schema::hasColumn('meta_copy_items', 'translations')) {
            return;
        }

        Schema::table('meta_copy_items', function (Blueprint $table) {
            $table->dropColumn('translations');
        });
    }
};

