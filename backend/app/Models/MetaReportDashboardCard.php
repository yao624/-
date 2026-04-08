<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaReportDashboardCard extends BaseModel
{
    protected $table = 'meta_report_dashboard_cards';

    protected $fillable = [
        'dashboard_id',
        'title',
        'chart_type',
        'shape',
        'sort_order',
        'query_config',
        'style_config',
    ];

    protected $casts = [
        'query_config' => 'array',
        'style_config' => 'array',
    ];

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(MetaReportDashboard::class, 'dashboard_id');
    }
}

