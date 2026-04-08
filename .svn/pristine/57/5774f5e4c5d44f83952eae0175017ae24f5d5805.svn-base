<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaReportDashboardShare extends BaseModel
{
    protected $table = 'meta_report_dashboard_shares';

    protected $fillable = [
        'dashboard_id',
        'subject_type',
        'subject_id',
        'can_edit',
    ];

    protected $casts = [
        'can_edit' => 'boolean',
    ];

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(MetaReportDashboard::class, 'dashboard_id');
    }
}

