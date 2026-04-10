<?php

namespace App\Models;

class MetaReportMetricDict extends BaseModel
{
    protected $table = 'meta_report_metric_dicts';

    protected $fillable = [
        'metric_key',
        'metric_name',
        'metric_name_en',
        'unit',
        'data_type',
        'aggregation_type',
        'supported_levels',
        'supported_chart_types',
        'is_filterable',
        'is_sortable',
        'is_permission_controlled',
        'permission_slug',
        'sort_order',
        'status',
        'description',
    ];

    protected $casts = [
        'supported_levels' => 'array',
        'supported_chart_types' => 'array',
        'is_filterable' => 'boolean',
        'is_sortable' => 'boolean',
        'is_permission_controlled' => 'boolean',
        'sort_order' => 'integer',
    ];
}
