<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaOrganization extends Model
{
    use HasFactory;

    protected $table = 'meta_organizations';

    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'sort',
        'is_del',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'sort' => 'integer',
        'is_del' => 'integer',
    ];

    public function children(): HasMany
    {
        return $this->hasMany(MetaOrganization::class, 'parent_id');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'meta_organization_users', 'organization_id', 'user_id');
    }
}
