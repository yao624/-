<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class FbAdset extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'notes',
        'fb_campaign_id',
        'pixel_id',
        'account_id',
        'billing_event',
        'budget_remaining',
        'bid_strategy',
        'bid_amount',
        'original_bid_amount',
        'campaign_id',
        'configured_status',
        'created_time',
        'daily_budget',
        'lifetime_budget',
        'effective_status',
        'source_id',
        'is_dynamic_creative',
        'name',
        'optimization_goal',
        'promoted_object',
        'source_adset_id',
        'start_time',
        'status',
        'targeting',
        'original_daily_budget',
        'original_lifetime_budget',
        'is_deleted_on_fb'
    ];

    protected $casts = [
        'promoted_object' => 'array',
        'is_dynamic_creative' => 'boolean',
        'targeting' => 'array',
        'is_deleted_on_fb' => 'boolean',
    ];
    public function tags() {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function fbAds()
    {
        return $this->hasMany(FbAd::class);
    }

    public function fbCampaign()
    {
        return $this->belongsTo(FbCampaign::class);
    }

    public function fbAdAccount()
    {
        return $this->belongsTo(FbAdAccount::class, 'account_id', 'source_id');
    }

    public function offerClicks()
    {
        return $this->hasMany(Click::class, 'fb_adset_source_id', 'source_id');
    }

    public function offerConversions()
    {
        return $this->hasMany(Conversion::class, 'fb_adset_source_id', 'source_id');
    }

    public function insights()
    {
        return $this->hasMany(FbAdsetInsight::class, 'adset_id', 'source_id');
    }

    public function rules()
    {
        return $this->morphToMany(Rule::class, 'ruleable');
    }

    public function get_metrics($startDate, $endDate, $timezone)
    {
        $insights = $this->insights()->whereBetween('date_start', [$startDate, $endDate])->get();

        // 转换日期到 AdAccount 时区
        $startDateInTimeZone = Carbon::createFromFormat('Y-m-d', $startDate, 'UTC')
            ->setTimezone($timezone)
            ->startOfDay();

        $endDateInTimeZone = Carbon::createFromFormat('Y-m-d', $endDate, 'UTC')
            ->setTimezone($timezone)
            ->endOfDay();

        $offerClicksCount = $this->offerClicks()
            ->whereBetween('clicks.click_datetime', [$startDateInTimeZone, $endDateInTimeZone])
            ->count();

        $offerConversionEvent = $this->offerConversions()
            ->whereBetween('conversions.conversion_datetime', [$startDateInTimeZone, $endDateInTimeZone]);
        $offerConversionsCount = (clone $offerConversionEvent)->where('price', '>', 0)->count();
        $offerConversionsValue = (clone $offerConversionEvent)->where('price', '>', 0)->sum('price');
        $offerLeads = (clone $offerConversionEvent)->where('price', '=', 0)->count();

        $total_spend = $insights->sum('spend');

        if ($offerLeads==0) {
            $taken_rate = 0;
        } else {
            $taken_rate = $offerConversionsCount / $offerLeads * 100;
            $taken_rate = round($taken_rate, 2);
        }
        if ($offerClicksCount > 0) {
            $offer_cpc = round($total_spend / $offerClicksCount, 2);
            $offer_epc = round($offerConversionsValue / $offerClicksCount, 2);
        } else {
            $offer_cpc = $total_spend;
            $offer_epc = null;
        }


        $aggregated = [
            'ad_account_id' => $this->fbAdAccount->source_id,
            'ad_account_name' => $this->fbAdAccount->name,
            'account_status' => $this->fbAdAccount->account_status,
            'disable_reason' => $this->fbAdAccount->disable_reason,
            'adtrust_dsl' => $this->fbAdAccount->adtrust_dsl,
            'currency' => $this->fbAdAccount->currency,
            'timezone' => $this->fbAdAccount->timezone_name,
            'campaign_id' => $this->fbCampaign->source_id,
            'campaign_name' => $this->fbCampaign->name,
            'adset_id' => $this->source_id,
            'adset_name' => $this->name,
            'impressions' => $insights->sum('impressions'),
            'daily_budget' => $this->daily_budget,
            'reach' => $insights->sum('reach'),
            'spend' => round($total_spend, 2),
            'purchase_roas' => round($insights->avg('purchase_roas_value'), 2),
            'frequency' => round($insights->avg('frequency'), 2),
            'clicks' => $insights->sum('clicks'),
            'link_clicks' => $insights->sum('inline_link_clicks'),
            'cpm' => round($insights->avg('cpm'), 2),
            'cpc' => round($insights->avg('cpc'), 2),
            'ctr' => round($insights->avg('ctr'), 2),
            'link_ctr' => round($insights->avg('inline_link_click_ctr'), 2),
            'link_cpc' => round($insights->avg('cost_per_inline_link_click'), 2),
            'add_to_cart' => $insights->sum('add_to_cart'),
            'purchase' => $insights->sum('purchase'),
            'lead' => $insights->sum('lead'),
            'comment' => $insights->sum('comment'),
            'cost_per_purchase' => round($insights->avg('cost_per_purchase'), 2),
            'cost_per_lead' => round($insights->avg('cost_per_lead'), 2),
            'cost_to_add_to_cart' => round($insights->avg('cost_to_add_to_cart'), 2),
            'offer_clicks' => $offerClicksCount,
            'offer_leads' => $offerLeads,
            'offer_conversions' => $offerConversionsCount,
            'offer_conversions_value' => $offerConversionsValue,
            'taken_rate' => $taken_rate,
            'offer_cpc' => $offer_cpc,
            'offer_epc' => $offer_epc,
            'enable_rule' => $this->enable_rule
        ];

        return $aggregated;
    }

    public function posts()
    {
        return $this->hasMany(FbPagePost::class, 'adset_source_id', 'source_id');
    }

}
