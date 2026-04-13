<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `link_tags` ENGINE=InnoDB");

        DB::statement("
            ALTER TABLE `link_tags`
            COMMENT = '链接模块独立标签表'
        ");

        DB::statement("
            ALTER TABLE `link_tags`
            MODIFY COLUMN `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
            MODIFY COLUMN `link_id` CHAR(26) NOT NULL COMMENT '关联 links.id',
            MODIFY COLUMN `user_id` VARCHAR(26) NOT NULL COMMENT '所属用户 ID',
            MODIFY COLUMN `name` VARCHAR(191) NOT NULL COMMENT '标签名称快照',
            MODIFY COLUMN `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT '创建时间',
            MODIFY COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT '更新时间',
            MODIFY COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT '软删除时间'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE `link_tags`
            COMMENT = ''
        ");

        DB::statement("
            ALTER TABLE `link_tags`
            MODIFY COLUMN `id` CHAR(26) NOT NULL,
            MODIFY COLUMN `link_id` CHAR(26) NOT NULL,
            MODIFY COLUMN `user_id` VARCHAR(26) NOT NULL,
            MODIFY COLUMN `name` VARCHAR(191) NOT NULL,
            MODIFY COLUMN `created_at` TIMESTAMP NULL DEFAULT NULL,
            MODIFY COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL,
            MODIFY COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL
        ");
    }
};
