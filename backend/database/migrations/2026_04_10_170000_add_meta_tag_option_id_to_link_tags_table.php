<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('link_tags', function (Blueprint $table) {
            $table->unsignedBigInteger('meta_tag_option_id')
                ->nullable()
                ->after('user_id')
                ->comment('关联 meta_tag_options.id');

            $table->index(['user_id', 'meta_tag_option_id'], 'idx_link_tags_user_option');
            $table->index(['link_id', 'meta_tag_option_id'], 'idx_link_tags_link_option');
        });
    }

    public function down(): void
    {
        Schema::table('link_tags', function (Blueprint $table) {
            $table->dropIndex('idx_link_tags_user_option');
            $table->dropIndex('idx_link_tags_link_option');
            $table->dropColumn('meta_tag_option_id');
        });
    }
};
