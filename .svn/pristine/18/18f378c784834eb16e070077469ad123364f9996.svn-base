<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrackerCampaign extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'id',
        'notes',

        'tracker_id',
        'campaign_name',
        'campaign_source_id',
        'alias',
    ];

    public function tracker()
    {
        return $this->belongsTo(Tracker::class, 'tracker_id');
    }

    public function offerClicks()
    {
        return $this->hasMany(TrackerOfferClick::class);
    }
}
