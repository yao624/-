<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FbPage
 *
 * @property string $source_id
 * @property string $access_token
 * @property string $name
 * @property string $notes
 * @property array $tokens
 */
class FbPage extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'source_id',
        'fan_count',
        'name',
        'promotion_eligible',
        'verification_status',
        'picture',
        'notes',
        'roles',
        'access_token',
        'tokens',
        'pbia',
    ];

    protected $casts = [
        'roles' => 'array',
        'tokens' => 'array'
    ];

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function fbAccounts()
    {
        return $this->belongsToMany(FbAccount::class, 'fb_account_page')
            ->using(CustomPivot::class)
            ->withPivot('tasks', 'role_human', 'is_active', 'source_id', 'name')
            ->withTimestamps();
    }

    public function fbAds()
    {
        return $this->belongsToMany(FbAd::class, 'fb_ad_fb_page')
            ->using(CustomPivot::class)
            ->withPivot('fb_page_source_id')
            ->withTimestamps();
    }

    public function fbApiTokens()
    {
        return $this->belongsToMany(FbApiToken::class, 'fb_api_token_fb_page', 'fb_page_id', 'fb_api_token_id')
            ->using(CustomPivot::class)
            ->withPivot( 'tasks') // 包含中间表的字段
            ->withTimestamps();
    }

    public function forms()
    {
        return $this->hasMany(FbPageForm::class, 'page_source_id', 'source_id');
    }

    public function posts()
    {
        return $this->hasMany(FbPagePost::class, 'page_source_id', 'source_id');
    }

    public function fbBms()
    {
        return $this->belongsToMany(FbBm::class, 'fb_bm_fb_page', 'fb_page_id', 'fb_bm_id')
            ->using(CustomPivot::class)
            ->withPivot( ['tasks', 'is_owner', 'role']) // 包含中间表的字段
            ->withTimestamps();
    }

    public function fbBusinessUsers()
    {
        return $this->belongsToMany(FbBusinessUser::class, 'fb_business_user_fb_page')
            ->using(CustomPivot::class)
            ->withPivot('role', 'tasks')
            ->withTimestamps();
    }

}
