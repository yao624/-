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
 * @property string $description
 * @property string $url
 * @property string $image_url
 * @property string $retailer_id
 * @property string $video_url
 * @property string $video_handler
 * @property string|null $notes
 */
class FbCatalogProduct extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'source_id',
        'currency',
        'fb_catalog_id',
        'name',
        'description',
        'url',
        'image_url',
        'retailer_id',
        'price',
        'video_url',
        'video_handler',
        'notes'
    ];

    public function tags() {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function catalog()
    {
        return $this->belongsTo(FbCatalog::class, 'fb_catalog_id', 'id');
    }

    public function productSets()
    {
        return $this->belongsToMany(FbCatalogProductSet::class, 'fb_product_fb_product_set')
            ->using(CustomPivot::class)
            ->withTimestamps();
    }
}
