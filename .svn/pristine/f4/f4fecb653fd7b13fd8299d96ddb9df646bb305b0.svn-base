<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $source_id
 * @property string $name
 * @property string|null $notes
 */
class FbCatalog extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'source_id',
        'name',
        'notes'
    ];

    public function tags() {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function fbBms()
    {
        return $this->belongsToMany(FbBm::class, 'fb_bm_fb_catalog', 'fb_catalog_id', 'fb_bm_id')
            ->using(CustomPivot::class)
            ->withPivot( ['relation', 'role', 'tasks']) // 包含中间表的字段
            ->withTimestamps();
    }

    public function products()
    {
        return $this->hasMany(FbCatalogProduct::class, 'fb_catalog_id', 'id');
    }

    public function productSets()
    {
        return $this->hasMany(FbCatalogProductSet::class, 'fb_catalog_id', 'id');
    }

    public function businessUsers()
    {
        return $this->belongsToMany(FbBusinessUser::class, 'fb_business_user_fb_catalog', 'fb_catalog_id', 'fb_business_user_id')
            ->using(CustomPivot::class)
            ->withPivot( ['relation', 'role', 'tasks']) // 包含中间表的字段
            ->withTimestamps();
    }

    public function pixels()
    {
        return $this->belongsToMany(FbPixel::class, 'fb_catalog_fb_pixel', 'fb_catalog_id', 'fb_pixel_id')
            ->using(CustomPivot::class)
            ->withTimestamps();
    }
}
