<?php

namespace App\Models;

use App\Events\PagePostSaved;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $primary_text
 * @property string $headline
 * @property string $description
 * @property string $post_type
 * @property string $url
 * @property string $permalink_url
 * @property Carbon $created_time
 * @property string $source_id
 * @property string $campaign_source_id
 * @property string $adset_source_id
 * @property string $ad_source_id
 * @property string $page_source_id
 * @property string $ad_account_source_id
 * @property string $notes
 * @property array<string, mixed> $media JSON 字段
 */
class FbPagePost extends BaseModel
{
    use HasFactory, SoftDeletes, HasUlids;

    protected $fillable = [
        'primary_text',
        'headline',
        'description',
        'post_type',
        'url',
        'permalink_url',
        'created_time',
        'source_id',
        'campaign_source_id',
        'adset_source_id',
        'ad_source_id',
        'page_source_id',
        'ad_account_source_id',
        'media',
        'notes',
        'url_tags',
    ];

    protected $casts = [
        'media' => 'array',
        'created_time' => 'datetime'
    ];

    protected $dispatchesEvents = [
        'saved' => PagePostSaved::class,
    ];

    public function page()
    {
        return $this->belongsTo(FbPage::class, 'page_source_id', 'source_id');
    }

    public function campaign()
    {
        return $this->belongsTo(FbCampaign::class, 'campaign_source_id', 'source_id');
    }

    public function adset()
    {
        return $this->belongsTo(FbAdset::class, 'adset_source_id', 'source_id');
    }

    public function ads()
    {
        return $this->hasMany(FbAd::class, 'source_id', 'ad_source_id');
    }

    public function adAccount()
    {
        return $this->belongsTo(FbAdAccount::class, 'ad_account_source_id', 'source_id');
    }

}
