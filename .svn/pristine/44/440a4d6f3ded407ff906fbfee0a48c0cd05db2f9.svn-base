<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read string $id
 * @property string $user_id
 * @property string $fb_ad_account_id
 * @property string $fb_ad_template_id
 * @property string $fb_account_id
 * @property string $fb_api_token_id
 * @property string $fb_pixel_id
 * @property string $fb_page_id
 * @property string $fb_page_form_id
 * @property string $material_id
 * @property string $copywriting_id
 * @property string $link_id
 * @property string $campaign_id
 * @property string $adset_id
 * @property string $ad_id
 * @property string $notes
 * @property string $operator_type
 * @property integer $launch_mode
 * @property string $post_source_id
 * @property boolean $is_success
 * @property string $failed_reason
 */
class AdLog extends BaseModel
{
    use HasFactory, SoftDeletes, HasUlids;

    protected $table = 'ad_logs';

    protected $fillable = [
        'notes',
        'user_id',
        'fb_ad_account_id',
        'fb_ad_template_id',
        'fb_account_id',
        'fb_api_token_id',
        'fb_pixel_id',
        'fb_page_id',
        'fb_page_form_id',
        'material_id',
        'copywriting_id',
        'link_id',
        'campaign_id',
        'adset_id',
        'ad_id',
        'operator_type',
        'launch_mode',
        'post_source_id',
        'is_success',
        'failed_reason',
    ];

    protected $casts = [
        'is_success' => 'boolean',
    ];

    public function materials() {
        return $this->belongsToMany(Material::class, 'adlog_material', 'adlog_id', 'material_id')
            ->using(CustomPivot::class)
            ->withTimestamps();
    }

    public function campaigns()
    {
        return $this->belongsToMany(
            FbCampaign::class,
            'adlog_campaign', // 中间表的名字
            'adlog_id', // AdLog 表的外键
            'campaign_source_id', // fb_campaigns 表的外键,
            'id',
            'source_id',
        )->using(CustomPivot::class)
            ->withPivot('campaign_source_id', 'campaign_created', 'campaign_failed_reason');

    }

    public function adsets()
    {
        return $this->belongsToMany(
            FbAdset::class,
            'adlog_adset', // 中间表的名字
            'adlog_id', // AdLog 表的外键
            'adset_source_id', // Material 表的外键
            'id',
            'source_id',
        )->using(CustomPivot::class)
            ->withPivot('adset_created', 'adset_failed_reason'); // 指定要返回的中间表字段
    }

    public function ads()
    {
        return $this->belongsToMany(
            FbAdset::class,
            'adlog_ad', // 中间表的名字
            'adlog_id', // AdLog 表的外键
            'ad_source_id', // Material 表的外键
            'id',
            'source_id',
        )->using(CustomPivot::class)
            ->withPivot('ad_created', 'ad_failed_reason'); // 指定要返回的中间表字段
    }

    public function adAccount()
    {
        return $this->belongsTo(FbAdAccount::class, 'fb_ad_account_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tokenUser()
    {
        return $this->belongsTo(FbApiToken::class, 'fb_api_token_id');
    }

    public function campaignPivot()
    {
        return $this->hasMany(AdLogPivotCampaign::class, 'adlog_id');
    }

    public function adsetPivot()
    {
        return $this->hasMany(AdLogPivotAdset::class, 'adlog_id');
    }

    public function adPivot()
    {
        return $this->hasMany(AdLogPivotAd::class, 'adlog_id');
    }

    public function adTemplate()
    {
        return $this->belongsTo(FbAdTemplate::class, 'fb_ad_template_id');
    }

    public function copywriting()
    {
        return $this->belongsTo(Copywriting::class, 'copywriting_id');
    }

    public function link()
    {
        return $this->belongsTo(Link::class, 'link_id');
    }

    public function page()
    {
        return $this->belongsTo(FbPage::class, 'fb_page_id');
    }

    public function pixel()
    {
        return $this->belongsTo(FbPixel::class, 'fb_pixel_id');
    }

}
