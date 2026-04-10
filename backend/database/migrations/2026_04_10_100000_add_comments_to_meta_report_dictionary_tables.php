<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `meta_report_metric_dicts` COMMENT = 'Meta 报表看板指标字典表'");
        DB::statement("ALTER TABLE `meta_report_dimension_dicts` COMMENT = 'Meta 报表看板维度字典表'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `meta_report_metric_dicts` COMMENT = ''");
        DB::statement("ALTER TABLE `meta_report_dimension_dicts` COMMENT = ''");
    }
};
