<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetaMaterialEditorTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'meta_material_editor_tasks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_name',
        'status',
        'material_type',
        'folder_option',
        'folder_id',
        'designer_id',
        'creator_id',
        'tags',
        'total_count',
        'success_count',
        'failed_count',
        'pending_count',
        'error_message',
        'created_by',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tags' => 'array',
        'total_count' => 'integer',
        'success_count' => 'integer',
        'failed_count' => 'integer',
        'pending_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'completed_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 获取任务的编辑项
     */
    public function editItems()
    {
        return $this->hasMany(MetaMaterialEditItem::class, 'task_id');
    }

    /**
     * 获取创建人
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 获取设计师
     */
    public function designer()
    {
        return $this->belongsTo(User::class, 'designer_id');
    }

    /**
     * 获取创意人
     */
    public function creatorUser()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * 获取文件夹
     */
    public function folder()
    {
        return $this->belongsTo(MetaCopyFolder::class, 'folder_id');
    }
}
