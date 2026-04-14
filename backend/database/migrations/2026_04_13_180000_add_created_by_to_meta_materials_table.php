<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meta_materials', function (Blueprint $table) {
            if (!Schema::hasColumn('meta_materials', 'created_by')) {
                $table->string('created_by', 64)->nullable()->after('creator_id')->comment('素材创建人用户ID');
                $table->index('created_by', 'idx_meta_materials_created_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('meta_materials', function (Blueprint $table) {
            if (Schema::hasColumn('meta_materials', 'created_by')) {
                $table->dropIndex('idx_meta_materials_created_by');
                $table->dropColumn('created_by');
            }
        });
    }
};
