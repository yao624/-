<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 文案维度投放日快照（列表展示数据用，无软删）。
 *
 * @property string $id
 * @property string $copy_item_id
 * @property string $stat_date
 * @property string $channel
 */
class MetaCopyPerformanceDaily extends Model
{
    use HasUlids;

    protected $table = 'meta_copy_performance_daily';

    protected $fillable = [
        'copy_item_id',
        'stat_date',
        'channel',
        'impressions',
        'clicks',
        'spend',
        'conversions',
        'revenue',
    ];

    protected $casts = [
        'stat_date' => 'date',
        'spend' => 'decimal:4',
        'revenue' => 'decimal:4',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(MetaCopyItem::class, 'copy_item_id');
    }
}
