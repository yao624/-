<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $source_id
 * @property string $name
 * @property array|null $filter
 */
class FbCatalogProductSet extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'source_id',
        'name',
        'filter',
        'fb_catalog_id'
    ];

    protected $casts = [
        'filter' => 'array'
    ];

    public function tags() {
        return $this->morphToMany(Tag::class, 'taggable');
    }
    public function products()
    {
        return $this->belongsToMany(FbCatalogProduct::class, 'fb_product_fb_product_set')
            ->using(CustomPivot::class)
            ->withTimestamps();
    }
}
