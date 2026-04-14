<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_material_permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('material_id')->comment('素材ID');
            $table->string('scope', 32)->default('enterprise')->comment('enterprise/users/departments/mixed');
            $table->boolean('include_sub_departments')->default(true)->comment('是否包含子部门');
            $table->string('created_by', 64)->nullable()->comment('设置权限的用户ID');
            $table->timestamps();

            $table->unique('material_id', 'uniq_meta_material_permissions_material');
            $table->index('scope', 'idx_meta_material_permissions_scope');
            $table->foreign('material_id', 'fk_meta_material_permissions_material')
                ->references('id')
                ->on('meta_materials')
                ->onDelete('cascade');
            $table->comment('素材可见范围主表');
        });

        Schema::create('meta_material_permission_subjects', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('permission_id')->comment('权限主表ID');
            $table->string('subject_type', 32)->comment('user/department');
            $table->string('subject_id', 64)->comment('用户ID或部门ID');
            $table->timestamps();

            $table->unique(['permission_id', 'subject_type', 'subject_id'], 'uniq_meta_material_perm_subject');
            $table->index(['subject_type', 'subject_id'], 'idx_meta_material_perm_subject_lookup');
            $table->foreign('permission_id', 'fk_meta_material_perm_subject_permission')
                ->references('id')
                ->on('meta_material_permissions')
                ->onDelete('cascade');
            $table->comment('素材可见范围主体明细表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_material_permission_subjects');
        Schema::dropIfExists('meta_material_permissions');
    }
};
