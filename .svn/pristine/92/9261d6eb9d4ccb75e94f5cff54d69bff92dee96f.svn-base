<?php

namespace App\Services;

use App\Models\Permission;

class DashboardMetricPermissionService
{
    public const DASHBOARD_SLUG = 'promotion-dashboard';
    public const METRIC_GROUP_SLUG = 'promotion-dashboard:metrics';

    public const METRICS = [
        ['key' => 'cost', 'name' => '花费'],
        ['key' => 'impressions', 'name' => '展示数'],
        ['key' => 'cpm', 'name' => '千次展示成本'],
        ['key' => 'clicks', 'name' => '点击数'],
        ['key' => 'cpc', 'name' => '点击成本'],
        ['key' => 'ctr', 'name' => '点击率'],
        ['key' => 'conversions', 'name' => '转化数'],
        ['key' => 'cpa', 'name' => '转化成本'],
        ['key' => 'conversionRate', 'name' => '转化率'],
        ['key' => 'register', 'name' => '注册数'],
        ['key' => 'payOrder', 'name' => '付费次数'],
    ];

    public static function ensurePermissions(): void
    {
        $dashboardPermission = Permission::query()
            ->where('slug', self::DASHBOARD_SLUG)
            ->orWhere('path', '/promotion/dashboard')
            ->first();

        if (!$dashboardPermission) {
            return;
        }

        $metricGroup = Permission::query()->firstOrCreate(
            ['slug' => self::METRIC_GROUP_SLUG],
            [
                'pid' => $dashboardPermission->id,
                'name' => '我的指标',
                'alias' => '我的指标',
                'type' => 'data',
                'status' => 1,
                'sort' => 1000,
            ]
        );

        if ((int) $metricGroup->pid !== (int) $dashboardPermission->id) {
            $metricGroup->pid = $dashboardPermission->id;
            $metricGroup->status = 1;
            $metricGroup->type = $metricGroup->type ?: 'data';
            $metricGroup->save();
        }

        foreach (self::METRICS as $index => $metric) {
            Permission::query()->firstOrCreate(
                ['slug' => self::metricSlug($metric['key'])],
                [
                    'pid' => $metricGroup->id,
                    'name' => $metric['name'],
                    'alias' => $metric['name'],
                    'type' => 'data',
                    'status' => 1,
                    'sort' => 1001 + $index,
                ]
            );
        }
    }

    public static function metricSlug(string $metricKey): string
    {
        return 'promotion-dashboard:metric:' . $metricKey;
    }
}
