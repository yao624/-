<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaReportDashboard extends BaseModel
{
    protected $table = 'meta_report_dashboards';

    protected $fillable = [
        'name',
        'folder_id',
        'location',
        'channel',
        'board_type',
        'group_compare',
        'default_filters',
        'last_saved_at',
        'status',
        'owner_user_id',
    ];

    protected $casts = [
        'group_compare' => 'boolean',
        'default_filters' => 'array',
        'last_saved_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MetaReportDashboardFolder::class, 'folder_id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(MetaReportDashboardCard::class, 'dashboard_id')->orderBy('sort_order');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(MetaReportDashboardShare::class, 'dashboard_id');
    }
}

