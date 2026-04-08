<?php

namespace App\Http\Services;

use App\Http\Resources\CardResourceForFbAdAccount;
use App\Http\Resources\CardResourceWithLatestTransaction;
use App\Http\Resources\FbCatalogProductSetResource;
use App\Http\Resources\FbPagePostResourceSimple;
use App\Http\Resources\FbPageResource;
use App\Http\Resources\FbPageResourceForMetrics;
use App\Http\Resources\TagResource;
use App\Models\FbAd;
use App\Models\FbAdAccount;
use App\Models\FbAdset;
use App\Models\FbCampaign;
use App\Models\FbCatalogProductSet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class MetricsService
{
    public function get_metrics_by_ad_account(mixed $ad_account_ids, mixed $date_start, mixed $date_stop,
                                              array $campaign_names = [], array $campaign_tags = [],
                                              bool $exclude_archived_campaigns = true, $user=null)
    {
        $fb_ad_accounts = FbAdAccount::whereIn('id', $ad_account_ids)->with([
            'insights' => function ($query) use ($date_start, $date_stop) {
                $query->whereBetween('date_start', [$date_start, $date_stop]);
            },
        ])->get();

        $metrics_list = [];
        foreach ($fb_ad_accounts as $fb_ad_account) {
            $metrics_list[] = $this->get_ad_account_metrics($fb_ad_account, $date_start, $date_stop, $campaign_names,
                $campaign_tags, $exclude_archived_campaigns, $user);
        }
        return $metrics_list;
    }

    public function get_metrics_by_campaign(mixed $campaign_ids, mixed $date_start, mixed $date_stop, $user=null): array
    {
        $fb_campaigns = FbCampaign::whereIn('id', $campaign_ids)->with([
            'insights' => function ($query) use ($date_start, $date_stop) {
                $query->whereBetween('date_start', [$date_start, $date_stop]);
            },
        ])->get();

        $metrics_list = [];
        foreach ($fb_campaigns as $fb_campaign) {
            $metrics_list[] = $this->get_campaign_metrics($fb_campaign, $date_start, $date_stop, $user);
        }

        return $metrics_list;
    }
    public function get_metrics_by_adset(mixed $adset_ids, mixed $date_start, mixed $date_stop, $user=null)
    {
        $fb_adsets = FbAdset::whereIn('id', $adset_ids)->with([
            'insights' => function ($query) use ($date_start, $date_stop) {
                $query->whereBetween('date_start', [$date_start, $date_stop]);
            },
            'fbCampaign',
        ])->get();

        $metrics_list = [];
        foreach ($fb_adsets as $fb_adset) {
            $metrics_list[] = $this->get_adset_metrics($fb_adset, $date_start, $date_stop, $user);
        }
        return $metrics_list;
    }

    public function get_metrics_by_ad(mixed $ad_ids, mixed $date_start, mixed $date_stop, $user=null): array
    {
        $fb_ads = FbAd::whereIn('id', $ad_ids)->with([
            'insights' => function ($query) use ($date_start, $date_stop) {
                $query->whereBetween('date_start', [$date_start, $date_stop]);
            },
            'fbCampaign',
            'fbAdset'
        ])->get();

        $metrics_list = [];
        foreach ($fb_ads as $fb_ad) {
            if (!$fb_ad->fbAdAccount || !$fb_ad->fbCampaign) {
                continue;
            }
            $metrics_list[] = $this->get_ad_metrics($fb_ad, $date_start, $date_stop, $user);
        }

        return $metrics_list;
    }

    public function get_rule_metrics_by_ad_account(array $action_list, $user)
    {
        // 第一步：提取 ID 列表
        $object_source_ids = array_column($action_list, 0);
        // 第二步：获取所有对应的 FbAdAccount 对象
        $fbAdAccounts = FbAdAccount::whereIn('source_id', $object_source_ids)->get();
        // 将 FbAdAccount 对象存储到一个数组中，以便通过 ID 快速访问
        $adAccountMap = $fbAdAccounts->keyBy('source_id');

        $metrics_list = [];
        foreach ($action_list as $action) {
            $fb_ad_account_id = $action[0];
            $date_start = $action[1];
            $date_stop = $action[2];

            if (isset($adAccountMap[$fb_ad_account_id])) {
                $fbAdAccount = $adAccountMap[$fb_ad_account_id];
                $metrics_list[] = $this->get_ad_account_metrics($fbAdAccount, $date_start, $date_stop, [], [], false, $user);
            }
        }

        return $metrics_list;
    }

    public function get_rule_metrics_by_campaign(array $action_list, $user)
    {
        // 第一步：提取 ID 列表
        $object_source_ids = array_column($action_list, 0);
        // 第二步：获取所有对应的对象
        $campaigns = FbCampaign::whereIn('source_id', $object_source_ids)->get();
        // 将对象存储到一个数组中，以便通过 ID 快速访问
        $campaignMap = $campaigns->keyBy('source_id');

        $metrics_list = [];
        foreach ($action_list as $action) {
            $object_source_id = $action[0];
            $date_start = $action[1];
            $date_stop = $action[2];

            if (isset($campaignMap[$object_source_id])) {
                $campaign = $campaignMap[$object_source_id];

                $campaign->load(['insights' => function ($query) use ($date_start, $date_stop) {
                    $query->whereBetween('date_start', [$date_start, $date_stop]);
                }]);
                $metrics_list[] = $this->get_campaign_metrics($campaign, $date_start, $date_stop, $user);
            }
        }

        return $metrics_list;
    }

    public function get_rule_metrics_by_adset(array $action_list, $user)
    {
        // 第一步：提取 ID 列表
        $object_source_ids = array_column($action_list, 0);
        // 第二步：获取所有对应的对象
        $adsets = FbAdset::whereIn('source_id', $object_source_ids)->get();
        // 将对象存储到一个数组中，以便通过 ID 快速访问
        $adsetMap = $adsets->keyBy('source_id');

        $metrics_list = [];
        foreach ($action_list as $action) {
            $object_source_id = $action[0];
            $date_start = $action[1];
            $date_stop = $action[2];

            if (isset($adsetMap[$object_source_id])) {
                $adset = $adsetMap[$object_source_id];
                $adset->load(['insights' => function ($query) use ($date_start, $date_stop) {
                    $query->whereBetween('date_start', [$date_start, $date_stop]);
                }]);
                $metrics_list[] = $this->get_adset_metrics($adset, $date_start, $date_stop, $user);
            }
        }

        return $metrics_list;
    }

    public function get_rule_metrics_by_ad(array $action_list, $user)
    {
        // 第一步：提取 ID 列表
        $object_source_ids = array_column($action_list, 0);
        // 第二步：获取所有对应的对象
        $ads = FbAd::whereIn('source_id', $object_source_ids)->get();
        // 将对象存储到一个数组中，以便通过 ID 快速访问
        $adMap = $ads->keyBy('source_id');

        $metrics_list = [];
        foreach ($action_list as $action) {
            $object_source_id = $action[0];
            $date_start = $action[1];
            $date_stop = $action[2];

            if (isset($adMap[$object_source_id])) {
                $ad = $adMap[$object_source_id];
                $ad->load(['insights' => function ($query) use ($date_start, $date_stop) {
                    $query->whereBetween('date_start', [$date_start, $date_stop]);
                }]);
                $metrics_list[] = $this->get_ad_metrics($ad, $date_start, $date_stop, $user);
            }
        }

        return $metrics_list;
    }

    private function get_ad_account_metrics(FbAdAccount $fb_ad_account, mixed $date_start, mixed $date_stop,
                                            array $campaign_names, array $campaign_tags,
                                            bool $exclude_archived_campaigns, $user)
    {
        // 设置时区
        $timezone = $fb_ad_account->timezone_name ?? 'UTC';

        // 创建Carbon实例
        $startDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_start, $timezone)->startOfDay()->setTimezone('UTC');
        $endDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_stop, $timezone)->endOfDay()->setTimezone('UTC');

        // 过滤 clicks 和 conversions
        $offerClicksCount = $fb_ad_account->offerClicks()->whereBetween('click_datetime', [$startDateInTimeZone, $endDateInTimeZone])->count();
        $offerConversionQuery = $fb_ad_account->offerConversions()->whereBetween('conversion_datetime', [$startDateInTimeZone, $endDateInTimeZone]);

        $offerConversionsCount = (clone $offerConversionQuery)->where('price', '>', 0)->count();
        $offerConversionsValue = (clone $offerConversionQuery)->where('price', '>', 0)->sum('price');
        $offerLeads = (clone $offerConversionQuery)->where('price', '=', 0)->count();
        $taken_rate = $offerLeads > 0 ? round(($offerConversionsCount / $offerLeads) * 100, 2) : 0;

        $offer_cpc = $offerClicksCount > 0 ? round($fb_ad_account->insights->sum('spend') / $offerClicksCount, 2) : 0;
        $offer_epc = $offerClicksCount > 0 ? round($offerConversionsValue / $offerClicksCount, 2) : 0;

        $spend = $fb_ad_account->insights->sum('spend');
        $profit = round(($offerConversionsValue - $spend), 2);
        $roi = ($spend != 0) ? round(($offerConversionsValue - $spend) / $spend, 2) : 0;
        // 计算 CPL 和 EPL
        $offer_cpl = $offerLeads != 0 ? number_format($spend / $offerLeads, 2) : 0;
        $offer_epl = $offerLeads != 0 ? number_format($offerConversionsValue / $offerLeads, 2) : 0;

        if ($exclude_archived_campaigns) {
//            Log::debug("exclude archived campaigns");
            $campaigns = $fb_ad_account->fbCampaigns()->whereNot('is_archived', true);
        } else {
//            Log::debug("contains archived campaigns");
            $campaigns = $fb_ad_account->fbCampaigns();
        }
        if (!empty($campaign_names)) {
//            Log::debug('filter campaign names');
//            Log::debug($campaign_names);
            $campaigns = $campaigns->where(function ($query) use ($campaign_names) {
                foreach ($campaign_names as $name) {
                    $query->orWhere('name', 'LIKE', '%' . $name . '%');
                }
            });
        }
        if (!empty($campaign_tags)) {
            $campaigns = $campaigns->whereHas('tags', function ($query) use ($campaign_tags) {
                $query->whereIn('tags.name', $campaign_tags);
            });
        }
        $campaignIds = $campaigns->pluck('id');
//        Log::debug("campaign ids:");
//        Log::debug($campaignIds);

        // 获取默认卡片对象并预加载最新交易
        $defaultCard = $fb_ad_account->defaultCard();
        if ($defaultCard) {
            $defaultCard->load(['transactions' => function($query) {
                $query->orderBy('transaction_date', 'desc')->limit(1);
            }]);
        }

        $aggregated = [
            'ad_account_id' => $fb_ad_account->source_id,
            'ad_account_name' => $fb_ad_account->name,
            'account_status' => $fb_ad_account->account_status,
            'funding' => $fb_ad_account->default_funding,
            'spend_cap' => $fb_ad_account->spend_cap,
            'balance' => $fb_ad_account->balance,
            'default_card' => $defaultCard ? new CardResourceWithLatestTransaction($defaultCard) : null,
            'disable_reason' => $fb_ad_account->disable_reason,
            'adtrust_dsl' => $fb_ad_account->adtrust_dsl,
            'total_spent' => $fb_ad_account->total_spent,
            'currency' => $fb_ad_account->currency,
            'timezone' => $timezone,
            'impressions' => $fb_ad_account->insights->sum('impressions'),
            'daily_budget' => $fb_ad_account->daily_budget,
            'reach' => $fb_ad_account->insights->sum('reach'),
            'spend' => round($fb_ad_account->insights->sum('spend'), 2),
            'original_spend' => round($fb_ad_account->insights->sum('original_spend'), 2),
            'purchase_roas' => round($fb_ad_account->insights->avg('purchase_roas_value'), 2),
            'frequency' => round($fb_ad_account->insights->avg('frequency'), 2),
            'clicks' => $fb_ad_account->insights->sum('clicks'),
            'link_clicks' => $fb_ad_account->insights->sum('inline_link_clicks'),
            'cpm' => round($fb_ad_account->insights->avg('cpm'), 2),
            'cpc' => round($fb_ad_account->insights->avg('cpc'), 2),
            'ctr' => round($fb_ad_account->insights->avg('ctr'), 2),
            'link_ctr' => round($fb_ad_account->insights->avg('inline_link_click_ctr'), 2),
            'link_cpc' => round($fb_ad_account->insights->avg('cost_per_inline_link_click'), 2),
            'add_to_cart' => $fb_ad_account->insights->sum('add_to_cart'),
            'purchase' => $fb_ad_account->insights->sum('purchase'),
            'lead' => $fb_ad_account->insights->sum('lead'),
            'comment' => $fb_ad_account->insights->sum('comment'),
            'cost_per_purchase' => round($fb_ad_account->insights->avg('cost_per_purchase'), 2),
            'cost_per_lead' => round($fb_ad_account->insights->avg('cost_per_lead'), 2),
            'cost_to_add_to_cart' => round($fb_ad_account->insights->avg('cost_to_add_to_cart'), 2),
            'offer_clicks' => $offerClicksCount,
            'offer_leads' => $offerLeads,
            'offer_conversions' => $offerConversionsCount,
            'offer_conversions_value' => $offerConversionsValue,
            'taken_rate' => $taken_rate,
            'offer_cpc' => $offer_cpc,
            'offer_epc' => $offer_epc,
            'offer_cpl' => $offer_cpl,
            'offer_epl' => $offer_epl,
            'roi' => $roi,
            'enable_rule' => $fb_ad_account->enable_rule,
            'campaign_ids' => $campaignIds,
            'id' => $fb_ad_account->id,
            'profit' => $profit,
            // TODO: ??
            'tags' => TagResource::collection($fb_ad_account->tags->where('user_id', $user->id)),
            'date_start' => $date_start,
            'date_stop' => $date_stop,
            'refresh_time' => $fb_ad_account->updated_at,
        ];

        return $aggregated;
    }

    /**
     * @param mixed $fb_campaign
     * @param mixed $date_start
     * @param mixed $date_stop
     * @return array
     */
    public function get_campaign_metrics(mixed $fb_campaign, mixed $date_start, mixed $date_stop, $user)
    {
        // 设置时区
        $timezone = $fb_campaign->fbAdAccount->timezone_name ?? 'UTC';

        // 创建Carbon实例
        $startDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_start, $timezone)->startOfDay()->setTimezone('UTC');
        $endDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_stop, $timezone)->endOfDay()->setTimezone('UTC');

        // 过滤 clicks 和 conversions
        $offerClicksCount = $fb_campaign->offerClicks()->whereBetween('click_datetime', [$startDateInTimeZone, $endDateInTimeZone])->count();
        $offerConversionQuery = $fb_campaign->offerConversions()->whereBetween('conversion_datetime', [$startDateInTimeZone, $endDateInTimeZone]);

        $offerConversionsCount = (clone $offerConversionQuery)->where('price', '>', 0)->count();
        $offerConversionsValue = (clone $offerConversionQuery)->where('price', '>', 0)->sum('price');
        $offerLeads = (clone $offerConversionQuery)->where('price', '=', 0)->count();
        $taken_rate = $offerLeads > 0 ? round(($offerConversionsCount / $offerLeads) * 100, 2) : 0;

        $offer_cpc = $offerClicksCount > 0 ? round($fb_campaign->insights->sum('spend') / $offerClicksCount, 2) : 0;
        $offer_epc = $offerClicksCount > 0 ? round($offerConversionsValue / $offerClicksCount, 2) : 0;

        $spend = $fb_campaign->insights->sum('spend');
        $profit = round(($offerConversionsValue - $spend), 2);
        $roi = ($spend != 0) ? round(($offerConversionsValue - $spend) / $spend, 2) : 0;

        // 计算 CPL 和 EPL
        $offer_cpl = $offerLeads != 0 ? number_format($spend / $offerLeads, 2) : 0;
        $offer_epl = $offerLeads != 0 ? number_format($offerConversionsValue / $offerLeads, 2) : 0;

        // 获取默认卡片对象并预加载最新交易
        $defaultCard = $fb_campaign->fbAdAccount->defaultCard();
        if ($defaultCard) {
            $defaultCard->load(['transactions' => function($query) {
                $query->orderBy('transaction_date', 'desc')->limit(1);
            }]);
        }

        $aggregated = [
            'ad_account_id' => $fb_campaign->fbAdAccount->source_id,
            'ad_account_ulid' => $fb_campaign->fbAdAccount->id,
            'ad_account_name' => $fb_campaign->fbAdAccount->name,
            'account_status' => $fb_campaign->fbAdAccount->account_status,
            'funding' => $fb_campaign->fbAdAccount->default_funding,
            'spend_cap' => $fb_campaign->fbAdAccount->spend_cap,
            'balance' => $fb_campaign->fbAdAccount->balance,
            'default_card' => $defaultCard ? new CardResourceWithLatestTransaction($defaultCard) : null,
            'disable_reason' => $fb_campaign->fbAdAccount->disable_reason,
            'adtrust_dsl' => $fb_campaign->fbAdAccount->adtrust_dsl,
            'is_topup' => $fb_campaign->fbAdAccount->is_topup,
            'total_spent' => $fb_campaign->fbAdAccount->total_spent,
            'currency' => $fb_campaign->fbAdAccount->currency,
            'timezone' => $timezone,
            'campaign_id' => $fb_campaign->source_id,
            'campaign_name' => $fb_campaign->name,
            'campaign_bid_strategy' => $fb_campaign->bid_strategy,
            'impressions' => $fb_campaign->insights->sum('impressions'),
            'daily_budget' => $fb_campaign->daily_budget,
            'lifetime_budget' => $fb_campaign->lifetime_budget,
            'reach' => $fb_campaign->insights->sum('reach'),
            'spend' => round($fb_campaign->insights->sum('spend'), 2),
            'original_spend' => round($fb_campaign->insights->sum('original_spend'), 2),
            'purchase_roas' => round($fb_campaign->insights->avg('purchase_roas_value'), 2),
            'frequency' => round($fb_campaign->insights->avg('frequency'), 2),
            'clicks' => $fb_campaign->insights->sum('clicks'),
            'link_clicks' => $fb_campaign->insights->sum('inline_link_clicks'),
            'cpm' => round($fb_campaign->insights->avg('cpm'), 2),
            'cpc' => round($fb_campaign->insights->avg('cpc'), 2),
            'ctr' => round($fb_campaign->insights->avg('ctr'), 2),
            'link_ctr' => round($fb_campaign->insights->avg('inline_link_click_ctr'), 2),
            'link_cpc' => round($fb_campaign->insights->avg('cost_per_inline_link_click'), 2),
            'add_to_cart' => $fb_campaign->insights->sum('add_to_cart'),
            'purchase' => $fb_campaign->insights->sum('purchase'),
            'lead' => $fb_campaign->insights->sum('lead'),
            'comment' => $fb_campaign->insights->sum('comment'),
            'cost_per_purchase' => round($fb_campaign->insights->avg('cost_per_purchase'), 2),
            'cost_per_lead' => round($fb_campaign->insights->avg('cost_per_lead'), 2),
            'cost_to_add_to_cart' => round($fb_campaign->insights->avg('cost_to_add_to_cart'), 2),
            'offer_clicks' => $offerClicksCount,
            'offer_leads' => $offerLeads,
            'offer_conversions' => $offerConversionsCount,
            'offer_conversions_value' => $offerConversionsValue,
            'taken_rate' => $taken_rate,
            'offer_cpc' => $offer_cpc,
            'offer_epc' => $offer_epc,
            'offer_cpl' => $offer_cpl,
            'offer_epl' => $offer_epl,
            'roi' => $roi,
            'enable_rule' => $fb_campaign->fbAdAccount->enable_rule,
            'adset_ids' => $fb_campaign->fbAdsets->pluck('id'),
            'id' => $fb_campaign->id,
            'effective_status' => $fb_campaign->effective_status,
            'profit' => $profit,
            'tags' => $fb_campaign->tags->where('user_id', $user->id),
            'is_deleted_on_fb' => $fb_campaign->is_deleted_on_fb,
            'status' => $fb_campaign->status,
            'created_time' => $fb_campaign->created_time,
            'refresh_time' => $fb_campaign->updated_at,
            'date_start' => $date_start,
            'date_stop' => $date_stop,
        ];

        return $aggregated;
    }


    /**
     * @param mixed $fb_adset
     * @param mixed $date_start
     * @param mixed $date_stop
     * @return array
     */
    public function get_adset_metrics(mixed $fb_adset, mixed $date_start, mixed $date_stop, $user)
    {
        // 设置时区
        $timezone = $fb_adset->fbAdAccount->timezone_name ?? 'UTC';

        // 创建Carbon实例
        $startDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_start, $timezone)->startOfDay()->setTimezone('UTC');
        $endDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_stop, $timezone)->endOfDay()->setTimezone('UTC');

        // 过滤 clicks 和 conversions
        $offerClicksCount = $fb_adset->offerClicks()->whereBetween('click_datetime', [$startDateInTimeZone, $endDateInTimeZone])->count();
        $offerConversionQuery = $fb_adset->offerConversions()->whereBetween('conversion_datetime', [$startDateInTimeZone, $endDateInTimeZone]);

        $offerConversionsCount = (clone $offerConversionQuery)->where('price', '>', 0)->count();
        $offerConversionsValue = (clone $offerConversionQuery)->where('price', '>', 0)->sum('price');
        $offerLeads = (clone $offerConversionQuery)->where('price', '=', 0)->count();
        $taken_rate = $offerLeads > 0 ? round(($offerConversionsCount / $offerLeads) * 100, 2) : 0;

        $offer_cpc = $offerClicksCount > 0 ? round($fb_adset->insights->sum('spend') / $offerClicksCount, 2) : 0;
        $offer_epc = $offerClicksCount > 0 ? round($offerConversionsValue / $offerClicksCount, 2) : 0;

        $spend = $fb_adset->insights->sum('spend');
        $profit = round(($offerConversionsValue - $spend), 2);
        $roi = ($spend != 0) ? round(($offerConversionsValue - $spend) / $spend, 2) : 0;
        // 计算 CPL 和 EPL
        $offer_cpl = $offerLeads != 0 ? number_format($spend / $offerLeads, 2) : 0;
        $offer_epl = $offerLeads != 0 ? number_format($offerConversionsValue / $offerLeads, 2) : 0;

        // 获取默认卡片对象并预加载最新交易
        $defaultCard = $fb_adset->fbAdAccount->defaultCard();
        if ($defaultCard) {
            $defaultCard->load(['transactions' => function($query) {
                $query->orderBy('transaction_date', 'desc')->limit(1);
            }]);
        }

        $aggregated = [
            'ad_account_id' => $fb_adset->fbAdAccount->source_id,
            'ad_account_ulid' => $fb_adset->fbAdAccount->id,
            'ad_account_name' => $fb_adset->fbAdAccount->name,
            'account_status' => $fb_adset->fbAdAccount->account_status,
            'disable_reason' => $fb_adset->fbAdAccount->disable_reason,
            'adtrust_dsl' => $fb_adset->fbAdAccount->adtrust_dsl,
            'funding' => $fb_adset->fbAdAccount->default_funding,
            'spend_cap' => $fb_adset->fbAdAccount->spend_cap,
            'balance' => $fb_adset->fbAdAccount->balance,
            'default_card' => $defaultCard ? new CardResourceWithLatestTransaction($defaultCard) : null,
            'currency' => $fb_adset->fbAdAccount->currency,
            'is_topup' => $fb_adset->fbAdAccount->is_topup,
            'total_spent' => $fb_adset->fbAdAccount->total_spent,
            'timezone' => $timezone,
            'campaign_id' => $fb_adset->fbCampaign->source_id,
            'campaign_uid' => $fb_adset->fbCampaign->id,
            'campaign_name' => $fb_adset->fbCampaign->name,
            'adset_id' => $fb_adset->source_id,
            'adset_name' => $fb_adset->name,
            'adset_bid_strategy' => $fb_adset->bid_strategy,
            'bid_amount' => $fb_adset->bid_amount,
            'campaign_bid_strategy' => $fb_adset->fbCampaign->bid_strategy,
            'impressions' => $fb_adset->insights->sum('impressions'),
            'daily_budget' => $fb_adset->daily_budget,
            'lifetime_budget' => $fb_adset->lifetime_budget,
            'reach' => $fb_adset->insights->sum('reach'),
            'spend' => round($fb_adset->insights->sum('spend'), 2),
            'original_spend' => round($fb_adset->insights->sum('original_spend'), 2),
            'purchase_roas' => round($fb_adset->insights->avg('purchase_roas_value'), 2),
            'frequency' => round($fb_adset->insights->avg('frequency'), 2),
            'clicks' => $fb_adset->insights->sum('clicks'),
            'link_clicks' => $fb_adset->insights->sum('inline_link_clicks'),
            'cpm' => round($fb_adset->insights->avg('cpm'), 2),
            'cpc' => round($fb_adset->insights->avg('cpc'), 2),
            'ctr' => round($fb_adset->insights->avg('ctr'), 2),
            'link_ctr' => round($fb_adset->insights->avg('inline_link_click_ctr'), 2),
            'link_cpc' => round($fb_adset->insights->avg('cost_per_inline_link_click'), 2),
            'add_to_cart' => $fb_adset->insights->sum('add_to_cart'),
            'purchase' => $fb_adset->insights->sum('purchase'),
            'lead' => $fb_adset->insights->sum('lead'),
            'comment' => $fb_adset->insights->sum('comment'),
            'cost_per_purchase' => round($fb_adset->insights->avg('cost_per_purchase'), 2),
            'cost_per_lead' => round($fb_adset->insights->avg('cost_per_lead'), 2),
            'cost_to_add_to_cart' => round($fb_adset->insights->avg('cost_to_add_to_cart'), 2),
            'offer_clicks' => $offerClicksCount,
            'offer_leads' => $offerLeads,
            'offer_conversions' => $offerConversionsCount,
            'offer_conversions_value' => $offerConversionsValue,
            'taken_rate' => $taken_rate,
            'offer_cpc' => $offer_cpc,
            'offer_epc' => $offer_epc,
            'offer_cpl' => $offer_cpl,
            'offer_epl' => $offer_epl,
            'roi' => $roi,
            'enable_rule' => $fb_adset->fbAdAccount->enable_rule,
            'id' => $fb_adset->id,
            'ad_ids' => $fb_adset->fbAds->pluck('id'),
            'effective_status' => $fb_adset->effective_status,
            'profit' => $profit,
            'is_deleted_on_fb' => $fb_adset->is_deleted_on_fb,
            'status' => $fb_adset->status,
            'tags' => $fb_adset->tags->where('user_id', $user->id),
            'created_time' => $fb_adset->created_time,
            'refresh_time' => $fb_adset->updated_at,
            'date_start' => $date_start,
            'date_stop' => $date_stop,
            'targeting' => $fb_adset->targeting,
        ];

        return $aggregated;
    }


    /**
     * @param mixed $fb_ad
     * @param mixed $date_start
     * @param mixed $date_stop
     * @return array
     */
    public function get_ad_metrics(mixed $fb_ad, mixed $date_start, mixed $date_stop, $user)
    {
        // 设置时区
        $timezone = $fb_ad->fbAdAccount->timezone_name ?? 'UTC';

        // 创建Carbon实例
        $startDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_start, $timezone)->startOfDay()->setTimezone('UTC');
        $endDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_stop, $timezone)->endOfDay()->setTimezone('UTC');

        // 过滤 clicks 和 conversions
        $offerClicksCount = $fb_ad->offerClicks()->whereBetween('click_datetime', [$startDateInTimeZone, $endDateInTimeZone])->count();
        $offerConversionQuery = $fb_ad->offerConversions()->whereBetween('conversion_datetime', [$startDateInTimeZone, $endDateInTimeZone]);

        $offerConversionsCount = (clone $offerConversionQuery)->where('price', '>', 0)->count();
        $offerConversionsValue = (clone $offerConversionQuery)->where('price', '>', 0)->sum('price');
        $offerLeads = (clone $offerConversionQuery)->where('price', '=', 0)->count();
        $taken_rate = $offerLeads > 0 ? round(($offerConversionsCount / $offerLeads) * 100, 2) : 0;

        $offer_cpc = $offerClicksCount > 0 ? round($fb_ad->insights->sum('spend') / $offerClicksCount, 2) : 0;
        $offer_epc = $offerClicksCount > 0 ? round($offerConversionsValue / $offerClicksCount, 2) : 0;

        $spend = $fb_ad->insights->sum('spend');
        $profit = round(($offerConversionsValue - $spend), 2);
        $roi = ($spend != 0) ? round(($offerConversionsValue - $spend) / $spend, 2) : 0;

        // 计算 CPL 和 EPL
        $offer_cpl = $offerLeads != 0 ? number_format($spend / $offerLeads, 2) : 0;
        $offer_epl = $offerLeads != 0 ? number_format($offerConversionsValue / $offerLeads, 2) : 0;

        // 检查creative中是否包含product_set_id并查询对应的FbCatalogProductSet
        $productSet = null;
        if ($fb_ad->creative && is_array($fb_ad->creative) && isset($fb_ad->creative['product_set_id'])) {
            $productSetId = $fb_ad->creative['product_set_id'];
            $fbCatalogProductSet = FbCatalogProductSet::where('source_id', $productSetId)->first();
            if ($fbCatalogProductSet) {
                $productSet = new FbCatalogProductSetResource($fbCatalogProductSet);
            }
        }

        $aggregated = [
            'ad_account_id' => $fb_ad->fbAdAccount->source_id,
            'ad_account_ulid' => $fb_ad->fbAdAccount->id,
            'ad_account_name' => $fb_ad->fbAdAccount->name,
            'account_status' => $fb_ad->fbAdAccount->account_status,
            'auto_add_languages' => $fb_ad->auto_add_languages,
            'disable_reason' => $fb_ad->fbAdAccount->disable_reason,
            'adtrust_dsl' => $fb_ad->fbAdAccount->adtrust_dsl,
            'currency' => $fb_ad->fbAdAccount->currency,
            'timezone' => $timezone,
            'campaign_id' => $fb_ad->fbCampaign->source_id,
            'campaign_name' => $fb_ad->fbCampaign->name,
            'adset_id' => $fb_ad->fbAdset->source_id,
            'adset_name' => $fb_ad->fbAdset->name,
            'ad_id' => $fb_ad->source_id,
            'ad_name' => $fb_ad->name,
            'impressions' => $fb_ad->insights->sum('impressions'),
            'daily_budget' => $fb_ad->daily_budget,
            'reach' => $fb_ad->insights->sum('reach'),
            'spend' => round($fb_ad->insights->sum('spend'), 2),
            'original_spend' => round($fb_ad->insights->sum('original_spend'), 2),
            'purchase_roas' => round($fb_ad->insights->avg('purchase_roas_value'), 2),
            'frequency' => round($fb_ad->insights->avg('frequency'), 2),
            'clicks' => $fb_ad->insights->sum('clicks'),
            'link_clicks' => $fb_ad->insights->sum('inline_link_clicks'),
            'cpm' => round($fb_ad->insights->avg('cpm'), 2),
            'cpc' => round($fb_ad->insights->avg('cpc'), 2),
            'ctr' => round($fb_ad->insights->avg('ctr'), 2),
            'link_ctr' => round($fb_ad->insights->avg('inline_link_click_ctr'), 2),
            'link_cpc' => round($fb_ad->insights->avg('cost_per_inline_link_click'), 2),
            'add_to_cart' => $fb_ad->insights->sum('add_to_cart'),
            'purchase' => $fb_ad->insights->sum('purchase'),
            'lead' => $fb_ad->insights->sum('lead'),
            'comment' => $fb_ad->insights->sum('comment'),
            'cost_per_purchase' => round($fb_ad->insights->avg('cost_per_purchase'), 2),
            'cost_per_lead' => round($fb_ad->insights->avg('cost_per_lead'), 2),
            'cost_to_add_to_cart' => round($fb_ad->insights->avg('cost_to_add_to_cart'), 2),
            'offer_clicks' => $offerClicksCount,
            'offer_leads' => $offerLeads,
            'offer_conversions' => $offerConversionsCount,
            'offer_conversions_value' => $offerConversionsValue,
            'taken_rate' => $taken_rate,
            'offer_cpc' => $offer_cpc,
            'offer_epc' => $offer_epc,
            'offer_cpl' => $offer_cpl,
            'offer_epl' => $offer_epl,
            'roi' => $roi,
            'enable_rule' => $fb_ad->fbAdAccount->enable_rule,
            'id' => $fb_ad->id,
            'effective_status' => $fb_ad->effective_status,
            'profit' => $profit,
            'creative' => $fb_ad->creative,
            'is_deleted_on_fb' => $fb_ad->is_deleted_on_fb,
            'status' => $fb_ad->status,
            'tags' => $fb_ad->tags->where('user_id', $user->id),
            'created_time' => $fb_ad->created_time,
            'refresh_time' => $fb_ad->updated_at,
            'preview_shareable_link' => $fb_ad->preview_shareable_link,
            'post' => $fb_ad->post ? new FbPagePostResourceSimple($fb_ad->post) : null,
            'current_page_name' => $fb_ad->post ? $fb_ad->post->page->name: '',
            'current_page_id' => $fb_ad->post ? $fb_ad->post->page->source_id: '',
            'date_start' => $date_start,
            'pages' => $fb_ad->fbPages ? FbPageResourceForMetrics::collection($fb_ad->fbPages): [],
            'product_set' => $productSet,
            'date_stop' => $date_stop,
        ];

        return $aggregated;
    }


}
