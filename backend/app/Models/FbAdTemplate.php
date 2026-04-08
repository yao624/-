<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FbAdTemplate extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'notes',
        'campaign_name',
        'adset_name',
        'ad_name',
        'bid_strategy',
        'bid_amount',
        'budget_level',
        'budget_type',
        'budget',
        'objective',
        'accelerated',
        'conversion_location',
        'optimization_goal',
        'pixel_event',
        'advantage_plus_audience',
        'genders',
        'age_min',
        'age_max',
        'primary_text',
        'headline_text',
        'description_text',
        'countries_included',
        'countries_excluded',
        'regions_included',
        'regions_excluded',
        'cities_included',
        'cities_excluded',
        'locales',
        'interests',
        'publisher_platforms',
        'facebook_positions',
        'instagram_positions',
        'messenger_positions',
        'audience_network_positions',
        'placement_mode',
        'device_platforms',
        'user_os',
        'wireless_carrier',
        'call_to_action',
        'url_params',
        'user_id',
    ];

    protected $casts = [
        'countries_included' => 'array',
        'countries_excluded' => 'array',
        'regions_included' => 'array',
        'regions_excluded' => 'array',
        'cities_included' => 'array',
        'cities_excluded' => 'array',
        'locales' => 'array',
        'interests' => 'array',
        'publisher_platforms' => 'array',
        'facebook_positions' => 'array',
        'instagram_positions' => 'array',
        'messenger_positions' => 'array',
        'audience_network_positions' => 'array',
        'device_platforms' => 'array',
        'user_os' => 'array',
        'wireless_carrier' => 'boolean',
        'advantage_plus_audience' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sharedWith()
    {
        return $this->belongsToMany(User::class, 'adtemplate_shares', 'adtemplate_id')->using(CustomPivot::class)
            ->withTimestamps();
    }

    public function isSharedWith($userId)
    {
        return $this->sharedWith()->where('users.id', $userId)->exists();
    }

}
