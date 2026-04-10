<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_report_metric_dicts', function (Blueprint $table) {
            $table->ulid('id')->primary()->comment('主键 ULID');
            $table->string('metric_key', 64)->unique()->comment('指标编码，供看板/报表/权限统一引用');
            $table->string('metric_name', 100)->comment('指标中文名称');
            $table->string('metric_name_en', 100)->nullable()->comment('指标英文名称');
            $table->string('unit', 32)->nullable()->comment('指标单位，如 currency/count/percent');
            $table->string('data_type', 32)->default('number')->comment('数据类型：number/integer/decimal/percent/currency');
            $table->string('aggregation_type', 32)->default('sum')->comment('聚合方式：sum/avg/count/rate/custom');
            $table->json('supported_levels')->nullable()->comment('适用层级，如 account/campaign/adset/ad');
            $table->json('supported_chart_types')->nullable()->comment('适用图表类型');
            $table->boolean('is_filterable')->default(false)->comment('是否支持作为指标筛选条件');
            $table->boolean('is_sortable')->default(true)->comment('是否支持排序');
            $table->boolean('is_permission_controlled')->default(true)->comment('是否受看板指标权限控制');
            $table->string('permission_slug', 128)->nullable()->comment('对应权限节点 slug');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序值');
            $table->string('status', 32)->default('active')->comment('状态：active/inactive');
            $table->text('description')->nullable()->comment('指标说明/口径说明');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'sort_order']);
        });
        DB::statement("ALTER TABLE `meta_report_metric_dicts` COMMENT = 'Meta 报表看板指标字典表'");

        Schema::create('meta_report_dimension_dicts', function (Blueprint $table) {
            $table->ulid('id')->primary()->comment('主键 ULID');
            $table->string('dimension_key', 64)->unique()->comment('维度编码，供看板/报表/筛选统一引用');
            $table->string('dimension_name', 100)->comment('维度中文名称');
            $table->string('dimension_name_en', 100)->nullable()->comment('维度英文名称');
            $table->string('value_type', 32)->default('string')->comment('值类型：string/date/datetime/number/enum');
            $table->json('supported_levels')->nullable()->comment('适用层级，如 account/campaign/adset/ad');
            $table->boolean('is_groupable')->default(true)->comment('是否支持分组展示');
            $table->boolean('is_filterable')->default(true)->comment('是否支持筛选');
            $table->boolean('is_default')->default(false)->comment('是否默认常用维度');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序值');
            $table->string('status', 32)->default('active')->comment('状态：active/inactive');
            $table->text('description')->nullable()->comment('维度说明');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'sort_order']);
        });
        DB::statement("ALTER TABLE `meta_report_dimension_dicts` COMMENT = 'Meta 报表看板维度字典表'");

        DB::table('meta_report_metric_dicts')->insert([
            $this->metricRow('cost', '花费', 'Cost', 'currency', 'currency', 'sum', ['account', 'campaign', 'adset', 'ad'], ['card-stat', 'table', 'line', 'area', 'bar', 'horizontal-bar', 'tile', 'scatter'], false, true, true, 'promotion-dashboard:metric:cost', 100, '广告实际消耗金额'),
            $this->metricRow('impressions', '展示数', 'Impressions', 'count', 'integer', 'sum', ['account', 'campaign', 'adset', 'ad'], ['card-stat', 'table', 'line', 'area', 'bar', 'horizontal-bar', 'tile', 'scatter'], false, true, true, 'promotion-dashboard:metric:impressions', 110, '广告展示次数'),
            $this->metricRow('cpm', '千次展示成本', 'CPM', 'currency', 'currency', 'avg', ['account', 'campaign', 'adset', 'ad'], ['card-stat', 'table', 'line', 'area', 'bar', 'horizontal-bar', 'tile', 'scatter'], true, true, true, 'promotion-dashboard:metric:cpm', 120, '每千次展示对应的平均花费'),
            $this->metricRow('clicks', '点击数', 'Clicks', 'count', 'integer', 'sum', ['account', 'campaign', 'adset', 'ad'], ['card-stat', 'table', 'line', 'area', 'bar', 'horizontal-bar', 'tile', 'scatter'], false, true, true, 'promotion-dashboard:metric:clicks', 130, '广告点击次数'),
            $this->metricRow('cpc', '点击成本', 'CPC', 'currency', 'currency', 'avg', ['account', 'campaign', 'adset', 'ad'], ['card-stat', 'table', 'line', 'area', 'bar', 'horizontal-bar', 'tile', 'scatter'], true, true, true, 'promotion-dashboard:metric:cpc', 140, '平均每次点击成本'),
            $this->metricRow('ctr', '点击率', 'CTR', 'percent', 'percent', 'rate', ['account', 'campaign', 'adset', 'ad'], ['card-stat', 'table', 'line', 'area', 'bar', 'horizontal-bar', 'tile', 'scatter'], true, true, true, 'promotion-dashboard:metric:ctr', 150, '点击数 / 展示数'),
            $this->metricRow('conversions', '转化数', 'Conversions', 'count', 'integer', 'sum', ['account', 'campaign', 'adset', 'ad'], ['card-stat', 'table', 'line', 'area', 'bar', 'horizontal-bar', 'tile', 'scatter'], false, true, true, 'promotion-dashboard:metric:conversions', 160, '广告转化次数'),
            $this->metricRow('cpa', '转化成本', 'CPA', 'currency', 'currency', 'avg', ['account', 'campaign', 'adset', 'ad'], ['card-stat', 'table', 'line', 'area', 'bar', 'horizontal-bar', 'tile', 'scatter'], true, true, true, 'promotion-dashboard:metric:cpa', 170, '平均每次转化成本'),
            $this->metricRow('conversionRate', '转化率', 'Conversion Rate', 'percent', 'percent', 'rate', ['account', 'campaign', 'adset', 'ad'], ['card-stat', 'table', 'line', 'area', 'bar', 'horizontal-bar', 'tile', 'scatter'], true, true, true, 'promotion-dashboard:metric:conversionRate', 180, '转化数 / 点击数'),
            $this->metricRow('register', '注册数', 'Register', 'count', 'integer', 'sum', ['account', 'campaign', 'adset', 'ad'], ['card-stat', 'table', 'line', 'area', 'bar', 'horizontal-bar', 'tile', 'scatter'], false, true, true, 'promotion-dashboard:metric:register', 190, '注册目标达成次数'),
            $this->metricRow('payOrder', '付费次数', 'Pay Orders', 'count', 'integer', 'sum', ['account', 'campaign', 'adset', 'ad'], ['card-stat', 'table', 'line', 'area', 'bar', 'horizontal-bar', 'tile', 'scatter'], false, true, true, 'promotion-dashboard:metric:payOrder', 200, '付费订单次数'),
        ]);

        DB::table('meta_report_dimension_dicts')->insert([
            $this->dimensionRow('date', '日期', 'Date', 'date', ['account', 'campaign', 'adset', 'ad'], true, true, true, 100, '按日期聚合查看趋势'),
            $this->dimensionRow('account', '广告账户', 'Ad Account', 'string', ['account', 'campaign', 'adset', 'ad'], true, true, true, 110, '广告账户维度'),
            $this->dimensionRow('campaign', '广告系列', 'Campaign', 'string', ['campaign', 'adset', 'ad'], true, true, true, 120, '广告系列维度'),
            $this->dimensionRow('adset', '广告组', 'Ad Set', 'string', ['adset', 'ad'], true, true, true, 130, '广告组维度'),
            $this->dimensionRow('ad', '广告', 'Ad', 'string', ['ad'], true, true, true, 140, '广告维度'),
            $this->dimensionRow('material', '素材', 'Creative', 'string', ['ad'], true, true, false, 150, '素材或创意维度'),
            $this->dimensionRow('country', '国家', 'Country', 'string', ['account', 'campaign', 'adset', 'ad'], true, true, true, 160, '投放国家/地区'),
            $this->dimensionRow('placement', '版位', 'Placement', 'string', ['campaign', 'adset', 'ad'], true, true, false, 170, '广告展示版位'),
            $this->dimensionRow('device', '设备', 'Device', 'string', ['campaign', 'adset', 'ad'], true, true, false, 180, '设备类型维度'),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_report_dimension_dicts');
        Schema::dropIfExists('meta_report_metric_dicts');
    }

    private function metricRow(
        string $key,
        string $name,
        string $nameEn,
        ?string $unit,
        string $dataType,
        string $aggregationType,
        array $levels,
        array $chartTypes,
        bool $filterable,
        bool $sortable,
        bool $permissionControlled,
        ?string $permissionSlug,
        int $sortOrder,
        ?string $description,
    ): array {
        return [
            'id' => (string) Str::ulid(),
            'metric_key' => $key,
            'metric_name' => $name,
            'metric_name_en' => $nameEn,
            'unit' => $unit,
            'data_type' => $dataType,
            'aggregation_type' => $aggregationType,
            'supported_levels' => json_encode($levels, JSON_UNESCAPED_UNICODE),
            'supported_chart_types' => json_encode($chartTypes, JSON_UNESCAPED_UNICODE),
            'is_filterable' => $filterable,
            'is_sortable' => $sortable,
            'is_permission_controlled' => $permissionControlled,
            'permission_slug' => $permissionSlug,
            'sort_order' => $sortOrder,
            'status' => 'active',
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function dimensionRow(
        string $key,
        string $name,
        string $nameEn,
        string $valueType,
        array $levels,
        bool $groupable,
        bool $filterable,
        bool $default,
        int $sortOrder,
        ?string $description,
    ): array {
        return [
            'id' => (string) Str::ulid(),
            'dimension_key' => $key,
            'dimension_name' => $name,
            'dimension_name_en' => $nameEn,
            'value_type' => $valueType,
            'supported_levels' => json_encode($levels, JSON_UNESCAPED_UNICODE),
            'is_groupable' => $groupable,
            'is_filterable' => $filterable,
            'is_default' => $default,
            'sort_order' => $sortOrder,
            'status' => 'active',
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
};
