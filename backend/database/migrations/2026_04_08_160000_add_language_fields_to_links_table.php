<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            if (!Schema::hasColumn('links', 'default_locale')) {
                $table->string('default_locale', 32)->nullable()->after('notes');
            }

            if (!Schema::hasColumn('links', 'language_variants')) {
                $table->json('language_variants')->nullable()->after('default_locale');
            }

            if (!Schema::hasColumn('links', 'import_source')) {
                $table->string('import_source', 32)->nullable()->after('language_variants');
            }
        });
    }

    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            foreach (['default_locale', 'language_variants', 'import_source'] as $column) {
                if (Schema::hasColumn('links', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
