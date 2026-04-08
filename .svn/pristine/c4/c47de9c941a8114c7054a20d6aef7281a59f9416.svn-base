<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'notes',
        'name',
        'filename',
        'filepath',
        'original_filename',
        'user_id',
        'type',
    ];

    public function tags() {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function sharedWith()
    {
        return $this->belongsToMany(User::class, 'material_shares')->using(CustomPivot::class)
            ->withTimestamps();
    }

    public function isSharedWith($userId)
    {
        return $this->sharedWith()->where('id', $userId)->exists();
    }

    public function mediaMaterials()
    {
        return $this->hasMany(MetaMediaMaterial::class, 'material_id');
    }

}
