<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaReportDashboardFolder extends BaseModel
{
    protected $table = 'meta_report_dashboard_folders';

    protected $fillable = [
        'owner_user_id',
        'parent_id',
        'name',
        'sort_order',
        'status',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function dashboards(): HasMany
    {
        return $this->hasMany(MetaReportDashboard::class, 'folder_id');
    }
}

