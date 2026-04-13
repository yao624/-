<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaPermission extends Model
{
    use HasFactory;

    protected $table = 'meta_permissions';

    protected $fillable = [
        'pid',
        'name',
        'slug',
        'type',
        'status',
        'alias',
        'icon',
        'path',
        'component',
        'redirect',
        'hide_in_menu',
        'hide_children_in_menu',
        'hide_in_breadcrumb',
        'sort',
    ];

    protected $casts = [
        'pid' => 'integer',
        'status' => 'integer',
        'hide_in_menu' => 'integer',
        'hide_children_in_menu' => 'integer',
        'hide_in_breadcrumb' => 'integer',
        'sort' => 'integer',
    ];

    public function children(): HasMany
    {
        return $this->hasMany(MetaPermission::class, 'pid');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }
}
