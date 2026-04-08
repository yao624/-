<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string name
 * @property string pixel
 */
class FbPixel extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'notes',
        'name',
        'pixel',
        'is_created_by_business',
        'is_unavailable',
        'is_dataset',
        'owner_business',
        'creator',
    ];

    protected $casts = [
        'owner_business' => 'array',
        'creator' => 'array',
        'is_dataset' => 'boolean',
        'is_unavailable' => 'boolean'
    ];

    public function tags() {
        return $this->morphToMany(Tag::class, 'taggable');
    }
    public function fbAdAccounts()
    {
        return $this->belongsToMany(FbAdAccount::class, 'fb_pixel_fb_ad_account')
            ->using(CustomPivot::class)
            ->withTimestamps();
    }

    public function fbBms()
    {
        return $this->belongsToMany(FbBm::class, 'fb_pixel_fb_bm')
            ->using(CustomPivot::class)
            ->withTimestamps();
    }

    public function catalogs()
    {
        return $this->belongsToMany(FbCatalog::class, 'fb_catalog_fb_pixel')
            ->using(CustomPivot::class)
            ->withTimestamps();
    }
}
