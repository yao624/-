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
 * @property Carbon $created_time
 * @property int $timezone_id
 * @property string $verification_status
 * @property string|null $two_factor_type
 * @property string $is_disabled_for_integrity_reasons
 * @property string|null $notes
 */
class FbBm extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'source_id',
        'name',
        'created_time',
        'timezone_id',
        'verification_status',
        'two_factor_type',
        'is_disabled_for_integrity_reasons',
        'notes'
    ];

    protected $casts = [
        'is_disabled_for_integrity_reasons' => 'boolean',
        'created_time' => 'datetime',
    ];
    public function tags() {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function fbBusinessUsers()
    {
        return $this->hasMany(FbBusinessUser::class);
    }

    public function fbAdAccounts()
    {
        return $this->belongsToMany(FbAdAccount::class, 'fb_ad_account_fb_bm')
            ->using(CustomPivot::class)
            ->withPivot('source_id', 'relation', 'tasks', 'role')
            ->withTimestamps();
    }

    public function pixels()
    {
        return $this->belongsToMany(FbPixel::class, 'fb_pixel_fb_bm')
            ->using(CustomPivot::class)
            ->withTimestamps();
    }

    public function fbApiTokens()
    {
        return $this->hasMany(FbApiToken::class, 'bm_id', 'source_id');
    }

    public function fbPages()
    {
        return $this->belongsToMany(FbPage::class, 'fb_bm_fb_page', 'fb_bm_id', 'fb_page_id')
            ->using(CustomPivot::class)
            ->withPivot( ['tasks', 'is_owner', 'role']) // 包含中间表的字段
            ->withTimestamps();
    }

    public function catalogs()
    {
        return $this->belongsToMany(FbCatalog::class, 'fb_bm_fb_catalog', 'fb_bm_id', 'fb_catalog_id')
            ->using(CustomPivot::class)
            ->withPivot( ['relation', 'role', 'tasks']) // 包含中间表的字段
            ->withTimestamps();
    }

    /**
     * FbBm 与 FbApp 的多对多关系
     * 中间表包含 relation 字段（owner/client）
     */
    public function fbApps()
    {
        return $this->belongsToMany(FbApp::class, 'fb_app_fb_bm')
            ->using(CustomPivot::class)
            ->withPivot('relation')
            ->withTimestamps();
    }

    /**
     * 获取此BM拥有的所有App
     */
    public function ownedApps()
    {
        return $this->fbApps()->wherePivot('relation', 'owner');
    }

    /**
     * 获取此BM作为客户端关联的所有App
     */
    public function clientApps()
    {
        return $this->fbApps()->wherePivot('relation', 'client');
    }
}
