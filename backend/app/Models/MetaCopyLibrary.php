<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Meta 文案库根表（个人库 / 企业库），对齐 XMP「我的文案库」「企业文案库」概念。
 *
 * @property string $id
 * @property string $name
 * @property string $type personal|enterprise
 * @property string $owner_user_id
 * @property array|null $visibility_scope
 * @property string $status
 */
class MetaCopyLibrary extends BaseModel
{
    protected $table = 'meta_copy_libraries';

    public const TYPE_PERSONAL = 'personal';

    public const TYPE_ENTERPRISE = 'enterprise';

    protected $fillable = [
        'name',
        'type',
        'owner_user_id',
        'visibility_scope',
        'status',
    ];

    protected $casts = [
        'visibility_scope' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function folders(): HasMany
    {
        return $this->hasMany(MetaCopyFolder::class, 'library_id');
    }

    public function rootFolders(): HasMany
    {
        return $this->folders()->whereNull('parent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MetaCopyItem::class, 'library_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(MetaCopyLibraryPermission::class, 'library_id');
    }
}
