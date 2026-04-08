<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $source_id
 * @property string $name
 * @property Carbon $created_time
 * @property string|null $notes
 */
class FbApp extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'source_id',
        'name',
        'created_time',
        'notes'
    ];

    protected $casts = [
        'created_time' => 'datetime',
    ];

    public function tags() {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * FbApp 与 FbBm 的多对多关系
     * 中间表包含 relation 字段（owner/client）
     */
    public function fbBms()
    {
        return $this->belongsToMany(FbBm::class, 'fb_app_fb_bm')
            ->using(CustomPivot::class)
            ->withPivot('relation')
            ->withTimestamps();
    }

    /**
     * FbApp 与 FbAdAccount 的多对多关系
     * 获取订阅此App的所有广告账户
     */
    public function fbAdAccounts()
    {
        return $this->belongsToMany(FbAdAccount::class, 'fb_app_fb_ad_account')
            ->using(CustomPivot::class)
            ->withTimestamps();
    }

    /**
     * 获取所有拥有此App的Business Manager
     */
    public function ownedByBms()
    {
        return $this->fbBms()->wherePivot('relation', 'owner');
    }

    /**
     * 获取所有作为客户端关联此App的Business Manager
     */
    public function clientBms()
    {
        return $this->fbBms()->wherePivot('relation', 'client');
    }

    /**
     * 获取使用此App的所有API Token
     * 通过 app 字段（存储此App的 source_id）进行关联
     */
    public function fbApiTokens()
    {
        return $this->hasMany(FbApiToken::class, 'app', 'source_id');
    }
}
