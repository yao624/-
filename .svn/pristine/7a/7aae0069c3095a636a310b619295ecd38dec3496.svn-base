<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrackerOfferClick extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'tracker_id',
        'tracker_campaign_id',
        'campaign_source_id',
        'subid',
        'ip',
        'sub_1',
        'sub_2',
        'sub_3',
        'sub_4',
        'sub_5',
        'click_date',
        'offer',
        'landing',
        'country_flag',
        'network_identifier',
    ];

    public function trackerCampaign()
    {
        return $this->belongsTo(TrackerCampaign::class, 'tracker_campaign_id');
    }

    public function tracker()
    {
        return $this->belongsTo(Tracker::class, 'tracker_id');
    }

}
