<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 企业文案库授权（用户或角色维度，后端校验写删管）。
 *
 * @property string $id
 * @property string $library_id
 * @property string $subject_type user|role
 * @property string $subject_id
 * @property bool $can_manage
 * @property bool $can_write
 * @property bool $can_delete
 */
class MetaCopyLibraryPermission extends BaseModel
{
    protected $table = 'meta_copy_library_permissions';

    public const SUBJECT_USER = 'user';

    public const SUBJECT_ROLE = 'role';

    protected $fillable = [
        'library_id',
        'subject_type',
        'subject_id',
        'can_manage',
        'can_write',
        'can_delete',
    ];

    protected $casts = [
        'can_manage' => 'boolean',
        'can_write' => 'boolean',
        'can_delete' => 'boolean',
    ];

    public function library(): BelongsTo
    {
        return $this->belongsTo(MetaCopyLibrary::class, 'library_id');
    }
}
