<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Click extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'transaction_id',
        'click_datetime',
        'network_id',
        'offer_source_id',
        'offer_source_name',
        'sub_1',
        'sub_2',
        'sub_3',
        'sub_4',
        'sub_5',
        'ip',
        'fb_campaign_source_id',
        'fb_adset_source_id',
        'fb_ad_source_id',
        'fb_pixel_number',
        'aff_id',
        'country_code'
    ];
    protected $casts = [
        'click_datetime' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(FbCampaign::class, 'fb_campaign_id');
    }

    public function adset()
    {
        return $this->belongsTo(FbCampaign::class, 'fb_adset_id');
    }

    public function ad()
    {
        return $this->belongsTo(FbCampaign::class, 'fb_ad_id');
    }

    public function network()
    {
        return $this->belongsTo(Network::class, 'network_id');
    }

}
