<?php

namespace App\Jobs;

use App\Http\Services\MetricsService;
use App\Models\FbAd;
use App\Models\FbAdAccount;
use App\Models\FbAdset;
use App\Models\FbCampaign;
use App\Models\Rule;
use App\Models\User;
use App\Utils\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AutomationCheckRule implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $date_start;
    private $date_stop;

    /**
     * Create a new job instance.
     */
    public function __construct($date_start=null, $date_stop=null)
    {
        $this->date_start = $date_start;
        $this->date_stop = $date_stop;
    }

    /**
     * Execute the job.
     */
    public function handle(MetricsService $metrics_service): void
    {
        Log::info(">> Start of Automation Check Rules: {$this->date_start}, {$this->date_stop}");
        // 找出所有有关的 Fb ad account,
        // 然后 list fb ad account,
        // list rules,
        // 再获取 metrics, 再 execute operation
        // 补充：遍历 Rule, 根据 Rule 的设置，查询出需要 apply 的 FB 对象的 id，放在一个 map 里面，同时这些 id 也要保存到一个总的 collect
        // 里面，然后再一次性统一查询出 metrics。然后再遍历一 rule, 根据之前的 map 保存的记录，和查询出的结果，去 apply rule

//        $ids_of_account_level = collect();
//        $ids_of_campaign_level = collect();
//        $ids_of_adset_level = collect();
//        $ids_of_ad_level = collect();
//        $ids_of_campaign_tag_level = collect();

        $all_acc_pair = [];
        $all_camp_pair = [];
        $all_adset_pair = [];
        $all_ad_pair = [];

        // TODO: 多用户的时候处理
        $user = User::query()->first();
        $rules = Rule::query()->where('is_active', true)->whereNotNull('user_id')->get();

        $rule_resource_mapping = collect();
        foreach ($rules as $rule) {
            Log::debug($rule);
            $rule_user_id = $rule->user_id;
            $scope = $rule['scope'];
            $date_preset = $rule['date_preset'];
            if ($scope === 'ad_account') {
                // 把 ad_account_ids 保存到 $ids_of_account_level，这是粗级别的
                $rule_ids_of_account_level = collect();
                $rule_ids_of_account_level = $rule_ids_of_account_level->merge(collect($rule['ad_account_ids']));
                // 如果resouce_id里面有包含 ad_account_id，也保存到 $ids_of_account_level,
                // 这是细一层次的，这一层次会在 ruleables 表中增加一条记录
                $rule_ids_of_account_level = $rule_ids_of_account_level->merge(collect($rule->fbAdAccounts->pluck('id')));

                $white_list = $rule['white_list'];
                if ($white_list) {
                    $wh = FbAdAccount::query()->whereIn('source_id', $white_list)->pluck('id');
                    $rule_ids_of_account_level = $rule_ids_of_account_level->diff($wh);
                }

                // 上面两步就已经包含了这个规则应用到的所有的 resource 层级的 id了
                // 去重一下，再找出这些 resource 的 source_id,
                // 根据 rule_id 创建一个map, 下一步执行规则的时候，就可以很容易找到需要对哪些 resource 应用规则,
                // 这个 map 的 value 是这些资源的 source_id, 因为 metrics 返回里面包含了fb 的 id,也就是数据库中的 source_id

                // 根据 fb account 不同的 timezone 获取对应的 date_start, date_stop
                // 数组 [ [ source_id, date_start, date_stop ], [ source_id, date_start, date_stop ] ]
                // 最后再去重

                $rule_acc_pair = $this->get_rule_acc_pair($rule_ids_of_account_level, $date_preset);
                $rule_resource_mapping->put(
                    $rule->id, $rule_acc_pair
                );

                // 添加到 $all_acc_pair 中，并对 $all_acc_pair 去重
                $all_acc_pair = array_merge($all_acc_pair, $rule_acc_pair);
                $all_acc_pair = array_map("unserialize", array_unique(array_map("serialize", $all_acc_pair)));

//                $rule_resource_mapping->put(
//                    $rule->id, FbAdAccount::query()->whereIn('id', $rule_ids_of_account_level->unique())->pluck('source_id')
//                );
//                $ids_of_account_level = $ids_of_account_level->merge($rule_ids_of_account_level);

            } elseif ($scope === 'campaign') {
                $rule_ids_of_campaign_level = collect();
                $ad_account_ids = collect($rule['ad_account_ids']);
                $rule_ids_of_campaign_level = $rule_ids_of_campaign_level->merge(FbCampaign::query()->whereIn('fb_ad_account_id', $ad_account_ids)->pluck('id'));
                $rule_ids_of_campaign_level = $rule_ids_of_campaign_level->merge($rule->fbCampaigns->pluck('id'));

                $white_list = $rule['white_list'];
                if ($white_list) {
                    $wh = FbCampaign::query()->whereIn('source_id', $white_list)->pluck('id');
                    $rule_ids_of_campaign_level = $rule_ids_of_campaign_level->diff($wh);
                }

//                $rule_resource_mapping->put(
//                    $rule->id, FbCampaign::query()->whereIn('id', $rule_ids_of_campaign_level->unique())->pluck('source_id')
//                );

                $rule_camp_pair = $this->get_rule_camp_pair($rule_ids_of_campaign_level, $date_preset);
                $rule_resource_mapping->put(
                    $rule->id, $rule_camp_pair
                );

                // 合并到 all 去，并对 all 数组去重
                $all_camp_pair = array_merge($all_camp_pair, $rule_camp_pair);
                $all_camp_pair = array_map('unserialize', array_unique(array_map('serialize', $all_camp_pair)));

//                $ids_of_campaign_level = $ids_of_campaign_level->merge($rule_ids_of_campaign_level);
            } elseif ($scope === 'adset') {

                $rule_ids_of_adset_level = collect();
                $ad_account_ids = collect($rule['ad_account_ids']);
                $ad_account_source_ids = FbAdAccount::query()->whereIn('id', $ad_account_ids)->pluck('source_id');
                // FbAdsets 表中 account_id 是指的广告账户的 id, 也就是数据库中广告账户的 source_id
                $rule_ids_of_adset_level = $rule_ids_of_adset_level->merge(FbAdset::query()->whereIn('account_id', $ad_account_source_ids)->pluck('id'));
                $rule_ids_of_adset_level = $rule_ids_of_adset_level->merge($rule->fbAdsets->pluck('id'));

                $white_list = $rule['white_list'];
                if ($white_list) {
                    $wh = FbAdset::query()->whereIn('source_id', $white_list)->pluck('id');
                    $rule_ids_of_adset_level = $rule_ids_of_adset_level->diff($wh);
                }

                $rule_adset_pair = $this->get_rule_adset_pair($rule_ids_of_adset_level, $date_preset);
                $rule_resource_mapping->put(
                    $rule->id, $rule_adset_pair
                );

                // 合并到 all 去，并对 all 数组去重
                $all_adset_pair = array_merge($all_adset_pair, $rule_adset_pair);
                $all_adset_pair = array_map('unserialize', array_unique(array_map('serialize', $all_adset_pair)));

//                $rule_resource_mapping->put(
//                    $rule->id, FbAdset::query()->whereIn('id', $rule_ids_of_adset_level->unique())->pluck('source_id')
//                );
//                $ids_of_adset_level = $ids_of_adset_level->merge($rule_ids_of_adset_level);
            } elseif ($scope === 'ad') {
                $rule_ids_of_ad_level = collect();
                $ad_account_ids = collect($rule['ad_account_ids']);
                $fb_campaign_ids = FbCampaign::query()->whereIn('fb_ad_account_id', $ad_account_ids)->pluck('id');
                $rule_ids_of_ad_level = $rule_ids_of_ad_level->merge(FbAd::query()->whereIn('fb_campaign_id', $fb_campaign_ids)->pluck('id'));
                $rule_ids_of_ad_level = $rule_ids_of_ad_level->merge($rule->fbAds->pluck('id'));

                $white_list = $rule['white_list'];
                if ($white_list) {
                    $wh = FbAd::query()->whereIn('source_id', $white_list)->pluck('id');
                    $rule_ids_of_ad_level = $rule_ids_of_ad_level->diff($wh);
                }

                $rule_ad_pair = $this->get_rule_ad_pair($rule_ids_of_ad_level, $date_preset);
                $rule_resource_mapping->put(
                    $rule->id, $rule_ad_pair
                );

                // 合并到 all 去，并对 all 数组去重
                $all_ad_pair = array_merge($all_ad_pair, $rule_ad_pair);
                $all_ad_pair = array_map('unserialize', array_unique(array_map('serialize', $all_ad_pair)));

//                $rule_resource_mapping->put(
//                    $rule->id, FbAd::query()->whereIn('id', $rule_ids_of_ad_level->unique())->pluck('source_id')
//                );
//                $ids_of_ad_level = $ids_of_ad_level->merge($rule_ids_of_ad_level);
            } elseif ($scope === 'camp_tag') {
                $ad_account_ids = collect($rule['ad_account_ids']);
                $tags = collect($rule['resource_ids']);
                // 查询 FbCampaign IDs，结合 FbAdAccount 和 Tag
//                $rule_ids_of_campaign_tag_level = FbCampaign::whereHas('fbAdAccount', function($query) use ($ad_account_ids) {
//                    $query->whereIn('id', $ad_account_ids);
//                })->whereHas('tags', function($query) use ($tags) {
//                        $query->whereIn('name', $tags);
//                })->pluck('id'); // 获取匹配到的 FbCampaign IDs
                $query = FbCampaign::query();
                // 如果 ad_account_ids 非空，则添加相关的 whereHas 条件
                if (!$ad_account_ids->isEmpty()) {
                    Log::debug('ad acc id not empty: ');
                    $query->whereHas('fbAdAccount', function($query) use ($ad_account_ids) {
                        $query->whereIn('id', $ad_account_ids);
                    });
                }
                if (!$tags->isEmpty()) {
                    $query->whereHas('tags', function($query) use ($rule_user_id, $tags) {
                        $query->whereIn('name', $tags)->where('tags.user_id', $rule_user_id);
                    });
                }
                $rule_ids_of_campaign_tag_level = $query->pluck('id');

                $white_list = $rule['white_list'];
                if ($white_list) {
                    # TODO: 这里可能需要处理权限
                    $wh = FbCampaign::query()->whereIn('source_id', $white_list)->pluck('id');
                    $rule_ids_of_campaign_tag_level = $rule_ids_of_campaign_tag_level->diff($wh);
                }

                Log::debug("ids: " . json_encode($rule_ids_of_campaign_tag_level));

                $rule_camp_pair = $this->get_rule_camp_pair($rule_ids_of_campaign_tag_level, $date_preset);
                $rule_resource_mapping->put(
                    $rule->id, $rule_camp_pair
                );

                // 合并到 all 去，并对 all 数组去重
                $all_camp_pair = array_merge($all_camp_pair, $rule_camp_pair);
                $all_camp_pair = array_map('unserialize', array_unique(array_map('serialize', $all_camp_pair)));

//                $rule_resource_mapping->put(
//                    $rule->id, FbCampaign::query()->whereIn('id', $rule_ids_of_campaign_tag_level->unique())->pluck('source_id')
//                );
//                FbCampaign::whereHas('tags', function($query){ $query->whereIn('name', ['test-rule-bad']); });
//                FbCampaign::query()->whereHas('fbAdAccount', function($query) {$query->whereIn('id', []);})->whereHas('tags', function($query){ $query->whereIn('name', ['test-rule-bad']); });;
//                $ids_of_campaign_level = $ids_of_campaign_level->merge($rule_ids_of_campaign_tag_level);
            } elseif ($scope === 'adset_tag') {
                $ad_account_ids = collect($rule['ad_account_ids']);
                $tags = collect($rule['resource_ids']);

                $query = FbAdset::query();
                // 如果 ad_account_ids 非空，则添加相关的 whereHas 条件
                if (!$ad_account_ids->isEmpty()) {
                    Log::debug('ad acc id not empty: ');
                    $query->whereHas('fbAdAccount', function($query) use ($ad_account_ids) {
                        $query->whereIn('id', $ad_account_ids);
                    });
                }
                if (!$tags->isEmpty()) {
                    $query->whereHas('tags', function($query) use ($rule_user_id, $tags) {
                        $query->whereIn('name', $tags)->where('tags.user_id', $rule_user_id);
                    });
                }
                $rule_ids_of_adset_tag_level = $query->pluck('id');

                $white_list = $rule['white_list'];
                if ($white_list) {
                    $wh = FbAdset::query()->whereIn('source_id', $white_list)->pluck('id');
                    $rule_ids_of_adset_tag_level = $rule_ids_of_adset_tag_level->diff($wh);
                }

                Log::debug("ids: " . json_encode($rule_ids_of_adset_tag_level));

                $rule_adset_pair = $this->get_rule_adset_pair($rule_ids_of_adset_tag_level, $date_preset);
                $rule_resource_mapping->put(
                    $rule->id, $rule_adset_pair
                );

                // 合并到 all 去，并对 all 数组去重
                $all_adset_pair = array_merge($all_adset_pair, $rule_adset_pair);
                $all_adset_pair = array_map('unserialize', array_unique(array_map('serialize', $all_adset_pair)));

//                $rule_resource_mapping->put(
//                    $rule->id, FbAdset::query()->whereIn('id', $rule_ids_of_adset_tag_level->unique())->pluck('source_id')
//                );
//                $ids_of_adset_level = $ids_of_adset_level->merge($rule_ids_of_adset_tag_level);
            } elseif ($scope === 'ad_tag') {
                $ad_account_ids = collect($rule['ad_account_ids']);
                $tags = collect($rule['resource_ids']);

                $query = FbAd::query();
                // 如果 ad_account_ids 非空，则添加相关的 whereHas 条件
                if (!$ad_account_ids->isEmpty()) {
                    Log::debug('ad acc id not empty: ');
                    $query->whereHas('fbAdAccountV2', function($query) use ($ad_account_ids) {
                        $query->whereIn('fb_ad_accounts.id', $ad_account_ids);
                    });
                }
                if (!$tags->isEmpty()) {
                    $query->whereHas('tags', function($query) use ($rule_user_id, $tags) {
                        $query->whereIn('tags.name', $tags)->where('tags.user_id', $rule_user_id);
                    });
                }
                $rule_ids_of_ad_tag_level = $query->pluck('fb_ads.id');

                $white_list = $rule['white_list'];
                if ($white_list) {
                    $wh = FbAd::query()->whereIn('source_id', $white_list)->pluck('id');
                    $rule_ids_of_ad_tag_level = $rule_ids_of_ad_tag_level->diff($wh);
                }

                $rule_ad_pair = $this->get_rule_ad_pair($rule_ids_of_ad_tag_level, $date_preset);
                $rule_resource_mapping->put(
                    $rule->id, $rule_ad_pair
                );

                // 合并到 all 去，并对 all 数组去重
                $all_ad_pair = array_merge($all_ad_pair, $rule_ad_pair);
                $all_ad_pair = array_map('unserialize', array_unique(array_map('serialize', $all_ad_pair)));

//                $rule_resource_mapping->put(
//                    $rule->id, FbAd::query()->whereIn('id', $rule_ids_of_ad_tag_level->unique())->pluck('source_id')
//                );
//                $ids_of_ad_level = $ids_of_ad_level->merge($rule_ids_of_ad_tag_level);
            }
        }

        // 把 id 去重一下
//        $ids_of_account_level = $ids_of_account_level->unique();
//        $ids_of_campaign_level = $ids_of_campaign_level->unique();
//        $ids_of_adset_level = $ids_of_adset_level->unique();
//        $ids_of_ad_level = $ids_of_ad_level->unique();

//        Log::debug("acc ids: " . json_encode($ids_of_account_level));
//        Log::debug("campaign ids: " . json_encode($ids_of_campaign_level));
//        Log::debug("adset ids: " . json_encode($ids_of_adset_level));

        // 获取这些数据的 metrics
//        $metrics_of_ad_accounts = $metrics_service->get_metrics_by_ad_account(ad_account_ids:$ids_of_account_level,
//            date_start: $this->date_start, date_stop: $this->date_stop, user: $user);
//        $metrics_of_campaigns = $metrics_service->get_metrics_by_campaign(campaign_ids: $ids_of_campaign_level,
//            date_start: $this->date_start, date_stop: $this->date_stop, user: $user);
//        $metrics_of_adsets = $metrics_service->get_metrics_by_adset(adset_ids: $ids_of_adset_level,
//            date_start: $this->date_start, date_stop: $this->date_stop, user: $user);
//        $metrics_of_ads = $metrics_service->get_metrics_by_ad(ad_ids: $ids_of_ad_level,
//            date_start: $this->date_start, date_stop: $this->date_stop, user: $user);

//        Log::debug("acc ids: " . json_encode($ids_of_account_level));
//        Log::debug("campaign ids: " . json_encode($ids_of_campaign_level));
//        Log::debug("adset ids: " . json_encode($ids_of_adset_level));

        $metrics_of_ad_accounts = $metrics_service->get_rule_metrics_by_ad_account($all_acc_pair, $user);
        $metrics_of_campaigns = $metrics_service->get_rule_metrics_by_campaign($all_camp_pair, $user);
        $metrics_of_adsets = $metrics_service->get_rule_metrics_by_adset($all_adset_pair, $user);
        $metrics_of_ads = $metrics_service->get_rule_metrics_by_ad($all_ad_pair, $user);

//        Log::debug("all adset pair: " . json_encode($all_adset_pair));

        foreach ($rules as $rule) {
            $scope = $rule['scope'];
            // 根据规则 id, 找出这个规则对应资源的 source_id 列表
            $rule_resource_ids = $rule_resource_mapping->get($rule['id']);
            $rule_resources_pair = $rule_resource_mapping->get($rule['id']);

            if ($scope === 'ad_account') {
                // 在 metrics 集合中找出 对应 source_id 的资源的 metrics 合集
//                $all_resource_metrics = collect($metrics_of_ad_accounts)->whereIn('ad_account_id', collect($rule_resource_ids));
                $resource_pairs_ids = collect($rule_resources_pair)->map(function($item) {
                    return [
                        'ad_account_id' => $item[0],
                        'date_start' => $item[1],
                        'date_stop' => $item[2],
                    ];
                });

                $filtered_metrics = collect($metrics_of_ad_accounts)->filter(function($metric) use ($resource_pairs_ids) {
                    return $resource_pairs_ids->contains(function ($value) use ($metric) {
                        return $value['ad_account_id'] === $metric['ad_account_id'] &&
                            $value['date_start'] === $metric['date_start'] &&
                            $value['date_stop'] === $metric['date_stop'];
                    });
                })->values()->all(); // 使用 values() 重置键，使用 all() 返回数组

                // 这些 metrics 需要被应用到这个 rule 上，遍历执行规则
                foreach ($filtered_metrics as $resource_metrics) {
//                    $rule->execute($resource_metrics, $resource_metrics['ad_account_name'], $resource_metrics['ad_account_name'], $resource_metrics['ad_account_id']);
                    AutomationExecuteRule::dispatch($rule, $resource_metrics)->onQueue('facebook');
                }
            } elseif ($scope === 'campaign') {
//                $all_resource_metrics = collect($metrics_of_campaigns)->whereIn('campaign_id', collect($rule_resource_ids));

                $resource_pairs_ids = collect($rule_resources_pair)->map(function($item) {
                    return [
                        'campaign_id' => $item[0],
                        'date_start' => $item[1],
                        'date_stop' => $item[2],
                    ];
                });
                Log::debug("resource_pairs_ids: " . json_encode($resource_pairs_ids));

                $filtered_metrics = collect($metrics_of_campaigns)->filter(function($metric) use ($resource_pairs_ids) {
                    return $resource_pairs_ids->contains(function ($value) use ($metric) {
                        return $value['campaign_id'] === $metric['campaign_id'] &&
                            $value['date_start'] === $metric['date_start'] &&
                            $value['date_stop'] === $metric['date_stop'];
                    });
                })->values()->all(); // 使用 values() 重置键，使用 all() 返回数组


                foreach ($filtered_metrics as $resource_metrics) {
//                    $rule->execute($resource_metrics, $resource_metrics['ad_account_name'], $resource_metrics['campaign_name'], $resource_metrics['campaign_id']);
                    AutomationExecuteRule::dispatch($rule, $resource_metrics)->onQueue('facebook');
                }
            } elseif ($scope === 'adset') {
//                $all_resource_metrics = collect($metrics_of_adsets)->whereIn('adset_id', collect($rule_resource_ids));

                $resource_pairs_ids = collect($rule_resources_pair)->map(function($item) {
                    return [
                        'adset_id' => $item[0],
                        'date_start' => $item[1],
                        'date_stop' => $item[2],
                    ];
                });
//                Log::debug("resource_pairs_ids: " . json_encode($resource_pairs_ids));

                $filtered_metrics = collect($metrics_of_adsets)->filter(function($metric) use ($resource_pairs_ids) {
                    return $resource_pairs_ids->contains(function ($value) use ($metric) {
                        return $value['adset_id'] === $metric['adset_id'] &&
                            $value['date_start'] === $metric['date_start'] &&
                            $value['date_stop'] === $metric['date_stop'];
                    });
                })->values()->all(); // 使用 values() 重置键，使用 all() 返回数组

//                Log::debug($filtered_metrics);


                foreach ($filtered_metrics as $resource_metrics) {
//                    $rule->execute($resource_metrics, $resource_metrics['ad_account_name'], $resource_metrics['adset_name'], $resource_metrics['adset_id']);
                    AutomationExecuteRule::dispatch($rule, $resource_metrics)->onQueue('facebook');
                }
            } elseif ($scope === 'ad') {
//                $all_resource_metrics = collect($metrics_of_ads)->whereIn('ad_id', collect($rule_resource_ids));
                $resource_pairs_ids = collect($rule_resources_pair)->map(function($item) {
                    return [
                        'ad_id' => $item[0],
                        'date_start' => $item[1],
                        'date_stop' => $item[2],
                    ];
                });

                $filtered_metrics = collect($metrics_of_ads)->filter(function($metric) use ($resource_pairs_ids) {
                    return $resource_pairs_ids->contains(function ($value) use ($metric) {
                        return $value['ad_id'] === $metric['ad_id'] &&
                            $value['date_start'] === $metric['date_start'] &&
                            $value['date_stop'] === $metric['date_stop'];
                    });
                })->values()->all(); // 使用 values() 重置键，使用 all() 返回数组

                foreach ($filtered_metrics as $resource_metrics) {
                    AutomationExecuteRule::dispatch($rule, $resource_metrics)->onQueue('facebook');
                }
            } elseif ($scope === 'camp_tag') {
//                $all_resource_metrics = collect($metrics_of_campaigns)->whereIn('campaign_id', collect($rule_resource_ids));

                $resource_pairs_ids = collect($rule_resources_pair)->map(function($item) {
                    return [
                        'campaign_id' => $item[0],
                        'date_start' => $item[1],
                        'date_stop' => $item[2],
                    ];
                });

                $filtered_metrics = collect($metrics_of_campaigns)->filter(function($metric) use ($resource_pairs_ids) {
                    return $resource_pairs_ids->contains(function ($value) use ($metric) {
                        return $value['campaign_id'] === $metric['campaign_id'] &&
                            $value['date_start'] === $metric['date_start'] &&
                            $value['date_stop'] === $metric['date_stop'];
                    });
                })->values()->all(); // 使用 values() 重置键，使用 all() 返回数组

                foreach ($filtered_metrics as $resource_metrics) {
//                    Log::debug("rule: {$rule['name']} \r\n, metrics: " . json_encode($resource_metrics));
                    AutomationExecuteRule::dispatch($rule, $resource_metrics)->onQueue('facebook');
                }
            } elseif ($scope === 'adset_tag') {
//                $all_resource_metrics = collect($metrics_of_adsets)->whereIn('adset_id', collect($rule_resource_ids));

                $resource_pairs_ids = collect($rule_resources_pair)->map(function($item) {
                    return [
                        'adset_id' => $item[0],
                        'date_start' => $item[1],
                        'date_stop' => $item[2],
                    ];
                });

                $filtered_metrics = collect($metrics_of_adsets)->filter(function($metric) use ($resource_pairs_ids) {
                    return $resource_pairs_ids->contains(function ($value) use ($metric) {
                        return $value['adset_id'] === $metric['adset_id'] &&
                            $value['date_start'] === $metric['date_start'] &&
                            $value['date_stop'] === $metric['date_stop'];
                    });
                })->values()->all(); // 使用 values() 重置键，使用 all() 返回数组

                foreach ($filtered_metrics as $resource_metrics) {
                    AutomationExecuteRule::dispatch($rule, $resource_metrics)->onQueue('facebook');
                }
            } elseif ($scope === 'ad_tag') {
//                $all_resource_metrics = collect($metrics_of_ads)->whereIn('ad_id', collect($rule_resource_ids));

                $resource_pairs_ids = collect($rule_resources_pair)->map(function($item) {
                    return [
                        'ad_id' => $item[0],
                        'date_start' => $item[1],
                        'date_stop' => $item[2],
                    ];
                });

                $filtered_metrics = collect($metrics_of_ads)->filter(function($metric) use ($resource_pairs_ids) {
                    return $resource_pairs_ids->contains(function ($value) use ($metric) {
                        return $value['ad_id'] === $metric['ad_id'] &&
                            $value['date_start'] === $metric['date_start'] &&
                            $value['date_stop'] === $metric['date_stop'];
                    });
                })->values()->all(); // 使用 values() 重置键，使用 all() 返回数组

                foreach ($filtered_metrics as $resource_metrics) {
                    AutomationExecuteRule::dispatch($rule, $resource_metrics)->onQueue('facebook');
                }
            }
        }

        Log::info(">> End of Automation Check Rules: {$this->date_start}, {$this->date_stop}");

    }

    private function get_rule_acc_pair(Collection $ids, string $date_preset)
    {
        $rule_acc_pair = [];
        $fbAccs = FbAdAccount::query()->whereIn('id', $ids->unique())->get();

        foreach ($fbAccs as $fbacc) {
            $source_id = $fbacc['source_id'];
            $timezone = $fbacc['timezone_name'];
            // 获取当前时间
            $currentDateTime = Carbon::now($timezone);
            switch ($date_preset) {
                case 'today':
                    $date_start = $currentDateTime->format('Y-m-d');
                    $date_stop = $currentDateTime->format('Y-m-d');
                    break;
                case 'last_2_days':
                    $date_start = $currentDateTime->subDays(1)->format('Y-m-d'); // 前一天
                    $date_stop = $currentDateTime->format('Y-m-d'); // 今天
                    break;
                default:
                    // 处理其他可能的 date_preset 值
                    $date_start = null;
                    $date_stop = null;
                    break;
            }
            // 只添加有效的日期对
            if ($date_start && $date_stop) {
                $rule_acc_pair[] = [$source_id, $date_start, $date_stop];
            }

            $rule_acc_pair[] = [$source_id, $date_start, $date_stop];
        }

        return $rule_acc_pair;
    }

    private function get_rule_camp_pair(Collection $ids, string $date_preset)
    {
        $uniqueIds = $ids->unique();
        // 批量获取所有相关的广告活动及其对应的广告账户
        $campaigns = FbCampaign::with('fbAdAccount')
            ->whereIn('id', $uniqueIds)
            ->get();

        $data = [];
        foreach ($campaigns as $campaign) {
            if ($campaign->fbAdAccount) {
                $timezone = $campaign->fbAdAccount->timezone_name;

                // 设置 Carbon 为正确的时区
                $date = Carbon::now($timezone);

                switch ($date_preset) {
                    case 'today':
                        $dateStart = $date->startOfDay()->toDateString();
                        $dateStop = $date->endOfDay()->toDateString();
                        break;

                    case 'last_2_days':
                        $dateStart = $date->clone()->subDay(1)->startOfDay()->toDateString();
                        $dateStop = $date->endOfDay()->toDateString(); // 即昨天到今天的结束时
                        break;

                    default:
                        // 处理其他情况或抛出异常
                        Log::debug("not processed date preset: " . $date_preset);
                        continue 2; // 跳过当前循环
                }

                // 组装数据
                $data[] = [$campaign->source_id, $dateStart, $dateStop];
            }
        }

        return $data;
    }

    private function get_rule_adset_pair(Collection $ids, string $date_preset)
    {
        $uniqueIds = $ids->unique();
        // 批量获取所有相关的广告活动及其对应的广告账户
        $adsets = FbAdset::with('fbAdAccount')
            ->whereIn('id', $uniqueIds)
            ->get();

        $data = [];
        foreach ($adsets as $adset) {
            if ($adset->fbAdAccount) {
                $timezone = $adset->fbAdAccount->timezone_name;

                // 设置 Carbon 为正确的时区
                $date = Carbon::now($timezone);

                switch ($date_preset) {
                    case 'today':
                        $dateStart = $date->startOfDay()->toDateString();
                        $dateStop = $date->endOfDay()->toDateString();
                        break;

                    case 'last_2_days':
                        $dateStart = $date->clone()->subDay(1)->startOfDay()->toDateString();
                        $dateStop = $date->endOfDay()->toDateString(); // 即昨天到今天的结束时
                        break;

                    default:
                        // 处理其他情况或抛出异常
                        break; // 跳过当前循环
                }

                // 组装数据
                $data[] = [$adset->source_id, $dateStart, $dateStop];
            }
        }

        return $data;
    }

    private function get_rule_ad_pair(Collection $ids, string $date_preset)
    {
        $uniqueIds = $ids->unique();
        // 批量获取所有相关的广告活动及其对应的广告账户
        $ads = FbAd::with('fbAdAccountV2')
            ->whereIn('id', $uniqueIds)
            ->get();

        $data = [];
        foreach ($ads as $ad) {
            if ($ad->fbAdAccountV2) {
                $timezone = $ad->fbAdAccountV2->timezone_name;

                // 设置 Carbon 为正确的时区
                $date = Carbon::now($timezone);

                switch ($date_preset) {
                    case 'today':
                        $dateStart = $date->startOfDay()->toDateString();
                        $dateStop = $date->endOfDay()->toDateString();
                        break;

                    case 'last_2_days':
                        $dateStart = $date->clone()->subDay(1)->startOfDay()->toDateString();
                        $dateStop = $date->endOfDay()->toDateString(); // 即昨天到今天的结束时
                        break;

                    default:
                        // 处理其他情况或抛出异常
                        break; // 跳过当前循环
                }

                // 组装数据
                $data[] = [$ad->source_id, $dateStart, $dateStop];
            }
        }

        return $data;
    }

    public function tags()
    {
        return [
            "Automation",
            "{$this->date_start}",
            "{$this->date_stop}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Automation Check Rule failed: ' . $exception->getMessage());
        $msg ="🚒🚒🚒 check rule failed, please check it ASP";
        Telegram::sendMessage($msg);
    }
}
