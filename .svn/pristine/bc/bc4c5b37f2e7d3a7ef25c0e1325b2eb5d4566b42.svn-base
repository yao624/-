<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Meta 文案库文件夹（多级，业务层限制最大 20 级）。
 *
 * @property string $id
 * @property string $library_id
 * @property string|null $parent_id
 * @property string $name
 * @property int $level
 * @property int $sort_order
 * @property int $direct_copy_count
 * @property int $total_copy_count
 * @property string|null $created_by
 */
class MetaCopyFolder extends BaseModel
{
    protected $table = 'meta_copy_folders';

    public const MAX_LEVEL = 20;

    protected $fillable = [
        'library_id',
        'parent_id',
        'name',
        'level',
        'sort_order',
        'direct_copy_count',
        'total_copy_count',
        'created_by',
    ];

    public function library(): BelongsTo
    {
        return $this->belongsTo(MetaCopyLibrary::class, 'library_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MetaCopyFolder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MetaCopyFolder::class, 'parent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MetaCopyItem::class, 'folder_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
