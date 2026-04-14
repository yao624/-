<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meta_upload_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('meta_upload_sessions', 'designer_ids_json')) {
                $table->json('designer_ids_json')->nullable()->after('designer_id')->comment('设计师ID列表JSON');
            }
            if (!Schema::hasColumn('meta_upload_sessions', 'creator_ids_json')) {
                $table->json('creator_ids_json')->nullable()->after('creator_id')->comment('创意人ID列表JSON');
            }
        });

        Schema::table('meta_materials', function (Blueprint $table) {
            if (!Schema::hasColumn('meta_materials', 'designer_ids_json')) {
                $table->json('designer_ids_json')->nullable()->after('designer_id')->comment('设计师ID列表JSON');
            }
            if (!Schema::hasColumn('meta_materials', 'creator_ids_json')) {
                $table->json('creator_ids_json')->nullable()->after('creator_id')->comment('创意人ID列表JSON');
            }
        });
    }

    public function down(): void
    {
        Schema::table('meta_upload_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('meta_upload_sessions', 'designer_ids_json')) {
                $table->dropColumn('designer_ids_json');
            }
            if (Schema::hasColumn('meta_upload_sessions', 'creator_ids_json')) {
                $table->dropColumn('creator_ids_json');
            }
        });

        Schema::table('meta_materials', function (Blueprint $table) {
            if (Schema::hasColumn('meta_materials', 'designer_ids_json')) {
                $table->dropColumn('designer_ids_json');
            }
            if (Schema::hasColumn('meta_materials', 'creator_ids_json')) {
                $table->dropColumn('creator_ids_json');
            }
        });
    }
};
