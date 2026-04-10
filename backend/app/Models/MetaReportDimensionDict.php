<?php

namespace App\Models;

class MetaReportDimensionDict extends BaseModel
{
    protected $table = 'meta_report_dimension_dicts';

    protected $fillable = [
        'dimension_key',
        'dimension_name',
        'dimension_name_en',
        'value_type',
        'supported_levels',
        'is_groupable',
        'is_filterable',
        'is_default',
        'sort_order',
        'status',
        'description',
    ];

    protected $casts = [
        'supported_levels' => 'array',
        'is_groupable' => 'boolean',
        'is_filterable' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];
}
