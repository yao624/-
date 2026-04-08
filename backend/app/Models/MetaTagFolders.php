<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaTagFolders extends Model
{
    use HasFactory;

    protected $table = 'meta_tag_folders';

    protected $fillable = [
        'name',
        'parent_id',
        'user_id',
        'sort',
        'is_del',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'user_id' => 'integer',
        'sort' => 'integer',
        'is_del' => 'integer',
    ];

    public function metaTags()
    {
        return $this->hasMany(MetaTags::class, 'folder_id');
    }
}
