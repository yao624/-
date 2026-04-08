<?php

namespace App\Http\Controllers;

use App\Models\FbAdAccount;
use App\Models\FbAdAccountInsight;
use App\Models\FbCampaign;
use App\Models\FbCampaignInsight;
use App\Models\FbAdset;
use App\Models\FbAdsetInsight;
use App\Models\Click;
use App\Models\Conversion;
use App\Models\User;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InsightsController extends BaseController
{
    /**
     * 获取用户广告账户的统计概览（高性能优化版本）
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function overview(Request $request)
    {
        $validatedData = $request->validate([
            'date_start' => 'required|date_format:Y-m-d',
            'date_stop' => 'required|date_format:Y-m-d|after_or_equal:date_start'
        ]);

        $date_start = $validatedData['date_start'];
        $date_stop = $validatedData['date_stop'];
        $excludeSourceIds = config('insights-config.excluded_ad_account_source_ids', []);
        $user = $request->user();

        // 根据用户权限获取FbAdAccount source_id列表
        if ($user->hasRole('admin')) {
            // Admin用户获取所有广告账户source_id
            $fbAdAccountQuery = FbAdAccount::query();
            if (!empty($excludeSourceIds)) {
                $fbAdAccountQuery->whereNotIn('source_id', $excludeSourceIds);
            }
            $fbAdAccountSourceIds = $fbAdAccountQuery->pluck('source_id')->toArray();
        } else {
            // 普通用户获取分配给他们的广告账户source_id
            $userAdAccounts = $user->fbAdAccounts;
            if (!empty($excludeSourceIds)) {
                $userAdAccounts = $userAdAccounts->whereNotIn('source_id', $excludeSourceIds);
            }
            $fbAdAccountSourceIds = $userAdAccounts->pluck('source_id')->toArray();
        }

        // 使用统一的计算方法
        $data = $this->calculateInsightsData($fbAdAccountSourceIds, $date_start, $date_stop);
        $data['date_start'] = $date_start;
        $data['date_stop'] = $date_stop;

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * 获取用户广告账户的统计概览（兼容版本，保留foreach但优化性能）
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function overviewWithForeach(Request $request)
    {
        $validatedData = $request->validate([
            'date_start' => 'required|date_format:Y-m-d',
            'date_stop' => 'required|date_format:Y-m-d|after_or_equal:date_start'
        ]);

        $date_start = $validatedData['date_start'];
        $date_stop = $validatedData['date_stop'];
        $excludeSourceIds = config('insights-config.excluded_ad_account_source_ids', []);
        $user = $request->user();

        // 根据用户权限获取FbAdAccount
        if ($user->hasRole('admin')) {
            $fbAdAccountQuery = FbAdAccount::query();
            if (!empty($excludeSourceIds)) {
                $fbAdAccountQuery->whereNotIn('source_id', $excludeSourceIds);
            }
            $fbAdAccounts = $fbAdAccountQuery->get();
        } else {
            $fbAdAccounts = $user->fbAdAccounts;
            if (!empty($excludeSourceIds)) {
                $fbAdAccounts = $fbAdAccounts->whereNotIn('source_id', $excludeSourceIds);
            }
        }

        // 🚀 性能优化: 预加载insights数据，并过滤掉spend为0的记录
        $fbAdAccounts->load(['insights' => function ($query) use ($date_start, $date_stop) {
            $query->whereBetween('date_start', [$date_start, $date_stop])
                  ->where('spend', '>', 0); // 只加载有消费的insights
        }]);

        // 🚀 性能优化: 先过滤出有消费的广告账户
        $accountsWithSpend = $fbAdAccounts->filter(function ($account) {
            return $account->insights->sum('spend') > 0;
        });

        // 初始化统计变量
        $totalSpend = 0;
        $totalLinkClicks = 0;
        $totalOfferClicks = 0;
        $totalOfferLeads = 0;
        $totalOfferConversions = 0;
        $totalRevenue = 0;

        // 设置时区和时间范围
        $startDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_start)->startOfDay()->setTimezone('UTC');
        $endDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_stop)->endOfDay()->setTimezone('UTC');

        // 🚀 只对有消费的账户进行offer相关查询
        foreach ($accountsWithSpend as $fbAdAccount) {
            // Spend和Link clicks从预加载的insights获取
            $totalSpend += $fbAdAccount->insights->sum('spend');
            $totalLinkClicks += $fbAdAccount->insights->sum('inline_link_clicks');

            // Offer相关指标从Click和Conversion模型获取
            $offerClicksCount = $fbAdAccount->offerClicks()
                ->whereBetween('click_datetime', [$startDateInTimeZone, $endDateInTimeZone])
                ->count();
            $totalOfferClicks += $offerClicksCount;

            $offerConversionQuery = $fbAdAccount->offerConversions()
                ->whereBetween('conversion_datetime', [$startDateInTimeZone, $endDateInTimeZone]);

            $offerLeads = (clone $offerConversionQuery)->where('price', '=', 0)->count();
            $offerConversions = (clone $offerConversionQuery)->where('price', '>', 0)->count();
            $revenue = (clone $offerConversionQuery)->where('price', '>', 0)->sum('price');

            $totalOfferLeads += $offerLeads;
            $totalOfferConversions += $offerConversions;
            $totalRevenue += $revenue;
        }

        // 计算利润和ROI
        $totalProfit = round($totalRevenue - $totalSpend, 2);
        $roi = ($totalSpend != 0) ? round($totalProfit / $totalSpend, 4) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'spend' => round($totalSpend, 2),
                'link_clicks' => $totalLinkClicks,
                'offer_clicks' => $totalOfferClicks,
                'leads' => $totalOfferLeads,
                'sales' => $totalOfferConversions,
                'revenue' => round($totalRevenue, 2),
                'profit' => $totalProfit,
                'roi' => $roi,
                'date_start' => $date_start,
                'date_stop' => $date_stop,
                'ad_accounts_count' => $fbAdAccounts->count(),
                'accounts_with_spend' => $accountsWithSpend->count()
            ]
        ]);
    }

    /**
     * 获取按用户分组的广告账户统计概览
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function overviewUsers(Request $request)
    {
        $validatedData = $request->validate([
            'date_start' => 'required|date_format:Y-m-d',
            'date_stop' => 'required|date_format:Y-m-d|after_or_equal:date_start'
        ]);

        $date_start = $validatedData['date_start'];
        $date_stop = $validatedData['date_stop'];
        $excludeSourceIds = config('insights-config.excluded_ad_account_source_ids', []);
        $user = $request->user();

        // 只有Admin用户可以访问此接口
        if (!$user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        // 获取所有广告账户（排除配置中的账户）
        $allFbAdAccountsQuery = FbAdAccount::query();
        if (!empty($excludeSourceIds)) {
            $allFbAdAccountsQuery->whereNotIn('source_id', $excludeSourceIds);
        }
        $allFbAdAccounts = $allFbAdAccountsQuery->with(['users'])->get();

        // 计算all数据（所有广告账户的汇总数据）
        $allData = $this->calculateInsightsData($allFbAdAccounts->pluck('source_id')->toArray(), $date_start, $date_stop);
        $allData['label'] = 'all';

        // 获取所有有分配广告账户的用户
        $users = User::whereHas('fbAdAccounts', function($query) use ($excludeSourceIds) {
            if (!empty($excludeSourceIds)) {
                $query->whereNotIn('source_id', $excludeSourceIds);
            }
        })->with(['fbAdAccounts' => function($query) use ($excludeSourceIds) {
            if (!empty($excludeSourceIds)) {
                $query->whereNotIn('source_id', $excludeSourceIds);
            }
        }])->get();

        // 计算每个用户的数据
        $usersData = [];
        $allUsersAccountIds = [];
        foreach ($users as $userItem) {
            $userAccountIds = $userItem->fbAdAccounts->pluck('source_id')->toArray();
            $allUsersAccountIds = array_merge($allUsersAccountIds, $userAccountIds);

            $userData = $this->calculateInsightsData($userAccountIds, $date_start, $date_stop);
            $userData['label'] = $userItem->name ?? "用户_{$userItem->id}";
            $userData['user_id'] = $userItem->id;
            $userData['user_email'] = $userItem->email;

            $usersData[] = $userData;
        }

        // 计算other数据（all - users）
        $allUsersAccountIds = array_unique($allUsersAccountIds);
        $otherAccountIds = $allFbAdAccounts->pluck('source_id')->toArray();
        $otherAccountIds = array_diff($otherAccountIds, $allUsersAccountIds);

        $otherData = $this->calculateInsightsData($otherAccountIds, $date_start, $date_stop);
        $otherData['label'] = 'other';

        return response()->json([
            'success' => true,
            'data' => [
                'all' => $allData,
                'users' => $usersData,
                'other' => $otherData,
                'date_start' => $date_start,
                'date_stop' => $date_stop,
                'total_users_count' => count($usersData)
            ]
        ]);
    }

    /**
     * 获取按广告账户分组的统计概览
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function overviewAdAccounts(Request $request)
    {
        $validatedData = $request->validate([
            'date_start' => 'required|date_format:Y-m-d',
            'date_stop' => 'required|date_format:Y-m-d|after_or_equal:date_start'
        ]);

        $date_start = $validatedData['date_start'];
        $date_stop = $validatedData['date_stop'];
        $excludeSourceIds = config('insights-config.excluded_ad_account_source_ids', []);
        $user = $request->user();

        // 根据用户权限获取FbAdAccount
        if ($user->hasRole('admin')) {
            // Admin用户获取所有广告账户
            $fbAdAccountsQuery = FbAdAccount::query();
            if (!empty($excludeSourceIds)) {
                $fbAdAccountsQuery->whereNotIn('source_id', $excludeSourceIds);
            }
            $fbAdAccounts = $fbAdAccountsQuery->with(['users'])->get();
        } else {
            // 普通用户获取分配给他们的广告账户
            $fbAdAccounts = $user->fbAdAccounts()->with(['users'])->get();
            if (!empty($excludeSourceIds)) {
                $fbAdAccounts = $fbAdAccounts->whereNotIn('source_id', $excludeSourceIds);
            }
        }

        // 只获取在指定时间段内有消费的广告账户
        $accountsWithSpend = FbAdAccountInsight::whereIn('account_id', $fbAdAccounts->pluck('source_id'))
            ->whereBetween('date_start', [$date_start, $date_stop])
            ->where('spend', '>', 0)
            ->distinct()
            ->pluck('account_id')
            ->toArray();

        // 过滤出有消费的广告账户
        $accountsData = [];
        foreach ($fbAdAccounts as $fbAdAccount) {
            if (in_array($fbAdAccount->source_id, $accountsWithSpend)) {
                // 计算该广告账户的统计数据
                $accountData = $this->calculateInsightsData([$fbAdAccount->source_id], $date_start, $date_stop);

                // 添加广告账户基本信息
                $accountData['account_id'] = $fbAdAccount->source_id;
                $accountData['account_name'] = $fbAdAccount->name;
                $accountData['account_status'] = $fbAdAccount->account_status;
                $accountData['currency'] = $fbAdAccount->currency;
                $accountData['default_funding'] = $fbAdAccount->default_funding;

                // 如果有关联用户，返回用户信息
                if ($fbAdAccount->users && $fbAdAccount->users->count() > 0) {
                    $accountData['users'] = $fbAdAccount->users->map(function ($user) {
                        return [
                            'user_id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email
                        ];
                    })->toArray();
                } else {
                    $accountData['users'] = [];
                }

                $accountsData[] = $accountData;
            }
        }

        // 按消费金额倒序排列
        usort($accountsData, function ($a, $b) {
            return $b['spend'] <=> $a['spend'];
        });

        // 计算汇总数据
        $allAccountSourceIds = array_column($accountsData, 'account_id');
        $summaryData = $this->calculateInsightsData($allAccountSourceIds, $date_start, $date_stop);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => array_merge($summaryData, [
                    'date_start' => $date_start,
                    'date_stop' => $date_stop
                ]),
                'accounts' => $accountsData,
                'total_accounts_with_spend' => count($accountsData)
            ]
        ]);
    }

    /**
     * 获取按Campaign分组的统计概览
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function overviewCampaigns(Request $request)
    {
        $validatedData = $request->validate([
            'date_start' => 'required|date_format:Y-m-d',
            'date_stop' => 'required|date_format:Y-m-d|after_or_equal:date_start'
        ]);

        $date_start = $validatedData['date_start'];
        $date_stop = $validatedData['date_stop'];
        $excludeSourceIds = config('insights-config.excluded_ad_account_source_ids', []);
        $user = $request->user();

        // 根据用户权限获取FbAdAccount
        if ($user->hasRole('admin')) {
            $fbAdAccountsQuery = FbAdAccount::query();
            if (!empty($excludeSourceIds)) {
                $fbAdAccountsQuery->whereNotIn('source_id', $excludeSourceIds);
            }
            $fbAdAccounts = $fbAdAccountsQuery->with(['users'])->get();
        } else {
            $fbAdAccounts = $user->fbAdAccounts()->with(['users'])->get();
            if (!empty($excludeSourceIds)) {
                $fbAdAccounts = $fbAdAccounts->whereNotIn('source_id', $excludeSourceIds);
            }
        }

        // 只获取在指定时间段内有消费的广告账户
        $accountsWithSpend = FbAdAccountInsight::whereIn('account_id', $fbAdAccounts->pluck('source_id'))
            ->whereBetween('date_start', [$date_start, $date_stop])
            ->where('spend', '>', 0)
            ->distinct()
            ->pluck('account_id')
            ->toArray();

        // 获取有消费账户下的所有Campaign
        $fbCampaigns = FbCampaign::whereHas('fbAdAccount', function($query) use ($accountsWithSpend) {
                $query->whereIn('source_id', $accountsWithSpend);
            })
            ->with(['fbAdAccount.users', 'insights' => function($query) use ($date_start, $date_stop) {
                $query->whereBetween('date_start', [$date_start, $date_stop]);
            }])
            ->get();

        // 过滤出有消费的Campaign
        $campaignsData = [];
        foreach ($fbCampaigns as $fbCampaign) {
            $campaignSpend = $fbCampaign->insights->sum('spend');
            if ($campaignSpend > 0) {
                $campaignData = $this->calculateCampaignInsightsData($fbCampaign, $date_start, $date_stop);

                // 添加广告账户信息
                $campaignData['ad_account_id'] = $fbCampaign->fbAdAccount->source_id;
                $campaignData['ad_account_name'] = $fbCampaign->fbAdAccount->name;
                $campaignData['account_status'] = $fbCampaign->fbAdAccount->account_status;
                $campaignData['currency'] = $fbCampaign->fbAdAccount->currency;
                $campaignData['default_funding'] = $fbCampaign->fbAdAccount->default_funding;

                // 如果有关联用户，返回用户信息
                if ($fbCampaign->fbAdAccount->users && $fbCampaign->fbAdAccount->users->count() > 0) {
                    $campaignData['users'] = $fbCampaign->fbAdAccount->users->map(function ($user) {
                        return [
                            'user_id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email
                        ];
                    })->toArray();
                } else {
                    $campaignData['users'] = [];
                }

                $campaignsData[] = $campaignData;
            }
        }

        // 按消费金额倒序排列
        usort($campaignsData, function ($a, $b) {
            return $b['spend'] <=> $a['spend'];
        });

        // 计算汇总数据
        $allCampaignSourceIds = array_column($campaignsData, 'campaign_id');
        $summaryData = $this->calculateCampaignsSummaryData($allCampaignSourceIds, $date_start, $date_stop);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => array_merge($summaryData, [
                    'date_start' => $date_start,
                    'date_stop' => $date_stop
                ]),
                'campaigns' => $campaignsData,
                'total_campaigns_with_spend' => count($campaignsData)
            ]
        ]);
    }

    /**
     * 获取按Adset分组的统计概览
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function overviewAdsets(Request $request)
    {
        $validatedData = $request->validate([
            'date_start' => 'required|date_format:Y-m-d',
            'date_stop' => 'required|date_format:Y-m-d|after_or_equal:date_start'
        ]);

        $date_start = $validatedData['date_start'];
        $date_stop = $validatedData['date_stop'];
        $excludeSourceIds = config('insights-config.excluded_ad_account_source_ids', []);
        $user = $request->user();

        // 根据用户权限获取FbAdAccount
        if ($user->hasRole('admin')) {
            $fbAdAccountsQuery = FbAdAccount::query();
            if (!empty($excludeSourceIds)) {
                $fbAdAccountsQuery->whereNotIn('source_id', $excludeSourceIds);
            }
            $fbAdAccounts = $fbAdAccountsQuery->with(['users'])->get();
        } else {
            $fbAdAccounts = $user->fbAdAccounts()->with(['users'])->get();
            if (!empty($excludeSourceIds)) {
                $fbAdAccounts = $fbAdAccounts->whereNotIn('source_id', $excludeSourceIds);
            }
        }

        // 只获取在指定时间段内有消费的广告账户
        $accountsWithSpend = FbAdAccountInsight::whereIn('account_id', $fbAdAccounts->pluck('source_id'))
            ->whereBetween('date_start', [$date_start, $date_stop])
            ->where('spend', '>', 0)
            ->distinct()
            ->pluck('account_id')
            ->toArray();

        // 获取有消费账户下的所有Adset
        $fbAdsets = FbAdset::whereHas('fbAdAccount', function($query) use ($accountsWithSpend) {
                $query->whereIn('source_id', $accountsWithSpend);
            })
            ->with(['fbAdAccount.users', 'fbCampaign', 'insights' => function($query) use ($date_start, $date_stop) {
                $query->whereBetween('date_start', [$date_start, $date_stop]);
            }])
            ->get();

        // 过滤出有消费的Adset
        $adsetsData = [];
        foreach ($fbAdsets as $fbAdset) {
            $adsetSpend = $fbAdset->insights->sum('spend');
            if ($adsetSpend > 0) {
                $adsetData = $this->calculateAdsetInsightsData($fbAdset, $date_start, $date_stop);

                // 添加广告账户信息
                $adsetData['ad_account_id'] = $fbAdset->fbAdAccount->source_id;
                $adsetData['ad_account_name'] = $fbAdset->fbAdAccount->name;
                $adsetData['account_status'] = $fbAdset->fbAdAccount->account_status;
                $adsetData['currency'] = $fbAdset->fbAdAccount->currency;
                $adsetData['default_funding'] = $fbAdset->fbAdAccount->default_funding;

                // 如果有关联用户，返回用户信息
                if ($fbAdset->fbAdAccount->users && $fbAdset->fbAdAccount->users->count() > 0) {
                    $adsetData['users'] = $fbAdset->fbAdAccount->users->map(function ($user) {
                        return [
                            'user_id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email
                        ];
                    })->toArray();
                } else {
                    $adsetData['users'] = [];
                }

                $adsetsData[] = $adsetData;
            }
        }

        // 按消费金额倒序排列
        usort($adsetsData, function ($a, $b) {
            return $b['spend'] <=> $a['spend'];
        });

        // 计算汇总数据
        $allAdsetSourceIds = array_column($adsetsData, 'adset_id');
        $summaryData = $this->calculateAdsetsSummaryData($allAdsetSourceIds, $date_start, $date_stop);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => array_merge($summaryData, [
                    'date_start' => $date_start,
                    'date_stop' => $date_stop
                ]),
                'adsets' => $adsetsData,
                'total_adsets_with_spend' => count($adsetsData)
            ]
        ]);
    }

    /**
     * 获取按Offer分组的统计概览
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function overviewOffers(Request $request)
    {
        $validatedData = $request->validate([
            'date_start' => 'required|date_format:Y-m-d',
            'date_stop' => 'required|date_format:Y-m-d|after_or_equal:date_start'
        ]);

        $date_start = $validatedData['date_start'];
        $date_stop = $validatedData['date_stop'];
        $excludeSourceIds = config('insights-config.excluded_ad_account_source_ids', []);
        $user = $request->user();

        // 根据用户权限获取FbAdAccount
        if ($user->hasRole('admin')) {
            $fbAdAccountsQuery = FbAdAccount::query();
            if (!empty($excludeSourceIds)) {
                $fbAdAccountsQuery->whereNotIn('source_id', $excludeSourceIds);
            }
            $fbAdAccounts = $fbAdAccountsQuery->with(['users'])->get();
        } else {
            $fbAdAccounts = $user->fbAdAccounts()->with(['users'])->get();
            if (!empty($excludeSourceIds)) {
                $fbAdAccounts = $fbAdAccounts->whereNotIn('source_id', $excludeSourceIds);
            }
        }

        // 只获取在指定时间段内有消费的广告账户
        $accountsWithSpend = FbAdAccountInsight::whereIn('account_id', $fbAdAccounts->pluck('source_id'))
            ->whereBetween('date_start', [$date_start, $date_stop])
            ->where('spend', '>', 0)
            ->distinct()
            ->pluck('account_id')
            ->toArray();

        // 获取有消费账户下的所有Campaign source_ids
        $campaignSourceIds = FbCampaign::whereHas('fbAdAccount', function($query) use ($accountsWithSpend) {
                $query->whereIn('source_id', $accountsWithSpend);
            })
            ->pluck('source_id')
            ->toArray();

        // 设置时区和时间范围
        $startDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_start)->startOfDay()->setTimezone('UTC');
        $endDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_stop)->endOfDay()->setTimezone('UTC');

        // 获取指定时间范围内的Conversion数据，按offer_source_name分组
        $offersData = [];
        if (!empty($campaignSourceIds)) {
            $conversions = Conversion::whereIn('fb_campaign_source_id', $campaignSourceIds)
                ->whereBetween('conversion_datetime', [$startDateInTimeZone, $endDateInTimeZone])
                ->whereNotNull('offer_source_name')
                ->where('offer_source_name', '!=', '')
                ->get();

            $offersGrouped = $conversions->groupBy('offer_source_name');

            foreach ($offersGrouped as $offerName => $offerConversions) {
                // 计算该offer的统计数据
                $offerData = $this->calculateOfferInsightsData($offerConversions, $offerName, $date_start, $date_stop);

                $offersData[] = $offerData;
            }
        }

        // 按收入倒序排列
        usort($offersData, function ($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });

        // 计算汇总数据
        $allOfferNames = array_column($offersData, 'offer_name');
        $summaryData = $this->calculateOffersSummaryData($allOfferNames, $campaignSourceIds, $date_start, $date_stop);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => array_merge($summaryData, [
                    'date_start' => $date_start,
                    'date_stop' => $date_stop
                ]),
                'offers' => $offersData,
                'total_offers' => count($offersData)
            ]
        ]);
    }

    /**
     * 获取趋势数据（按天统计）
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trends(Request $request)
    {
        $validatedData = $request->validate([
            'date_start' => 'required|date_format:Y-m-d',
            'date_stop' => 'required|date_format:Y-m-d|after_or_equal:date_start'
        ]);

        $date_start = $validatedData['date_start'];
        $date_stop = $validatedData['date_stop'];
        $excludeSourceIds = config('insights-config.excluded_ad_account_source_ids', []);
        $user = $request->user();

        // 根据用户权限获取FbAdAccount source_id列表
        if ($user->hasRole('admin')) {
            $fbAdAccountQuery = FbAdAccount::query();
            if (!empty($excludeSourceIds)) {
                $fbAdAccountQuery->whereNotIn('source_id', $excludeSourceIds);
            }
            $fbAdAccountSourceIds = $fbAdAccountQuery->pluck('source_id')->toArray();
        } else {
            $userAdAccounts = $user->fbAdAccounts;
            if (!empty($excludeSourceIds)) {
                $userAdAccounts = $userAdAccounts->whereNotIn('source_id', $excludeSourceIds);
            }
            $fbAdAccountSourceIds = $userAdAccounts->pluck('source_id')->toArray();
        }

        // 生成日期范围内的每一天
        $startDate = Carbon::createFromFormat('Y-m-d', $date_start);
        $endDate = Carbon::createFromFormat('Y-m-d', $date_stop);
        $dateRange = [];

        while ($startDate <= $endDate) {
            $dateRange[] = $startDate->format('Y-m-d');
            $startDate->addDay();
        }

        // 为每一天计算统计数据
        $trendsData = [];
        foreach ($dateRange as $currentDate) {
            $dayData = $this->calculateInsightsData($fbAdAccountSourceIds, $currentDate, $currentDate);
            $dayData['date'] = $currentDate;
            $trendsData[] = $dayData;
        }

        // 计算整体汇总数据
        $summaryData = $this->calculateInsightsData($fbAdAccountSourceIds, $date_start, $date_stop);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => array_merge($summaryData, [
                    'date_start' => $date_start,
                    'date_stop' => $date_stop
                ]),
                'trends' => $trendsData,
                'total_days' => count($trendsData)
            ]
        ]);
    }

    /**
     * 获取按用户分组的趋势数据
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trendsUsers(Request $request)
    {
        $validatedData = $request->validate([
            'date_start' => 'required|date_format:Y-m-d',
            'date_stop' => 'required|date_format:Y-m-d|after_or_equal:date_start'
        ]);

        $date_start = $validatedData['date_start'];
        $date_stop = $validatedData['date_stop'];
        $excludeSourceIds = config('insights-config.excluded_ad_account_source_ids', []);
        $user = $request->user();

        // 只有Admin用户可以访问此接口
        if (!$user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        // 获取所有有分配广告账户的用户
        $users = User::whereHas('fbAdAccounts', function($query) use ($excludeSourceIds) {
            if (!empty($excludeSourceIds)) {
                $query->whereNotIn('source_id', $excludeSourceIds);
            }
        })->with(['fbAdAccounts' => function($query) use ($excludeSourceIds) {
            if (!empty($excludeSourceIds)) {
                $query->whereNotIn('source_id', $excludeSourceIds);
            }
        }])->get();

        // 生成日期范围内的每一天
        $startDate = Carbon::createFromFormat('Y-m-d', $date_start);
        $endDate = Carbon::createFromFormat('Y-m-d', $date_stop);
        $dateRange = [];

        while ($startDate <= $endDate) {
            $dateRange[] = $startDate->format('Y-m-d');
            $startDate->addDay();
        }

        // 获取所有广告账户（用于计算others数据）
        $allFbAdAccountsQuery = FbAdAccount::query();
        if (!empty($excludeSourceIds)) {
            $allFbAdAccountsQuery->whereNotIn('source_id', $excludeSourceIds);
        }
        $allFbAdAccountSourceIds = $allFbAdAccountsQuery->pluck('source_id')->toArray();

        // 为每一天每个用户计算统计数据
        $trendsData = [];
        foreach ($dateRange as $currentDate) {
            $dayUsers = [];
            $allUsersAccountIds = [];

            // 计算每个用户的数据
            foreach ($users as $userItem) {
                $userAccountSourceIds = $userItem->fbAdAccounts->pluck('source_id')->toArray();
                $allUsersAccountIds = array_merge($allUsersAccountIds, $userAccountSourceIds);

                $userData = $this->calculateInsightsData($userAccountSourceIds, $currentDate, $currentDate);

                $userData['user_id'] = $userItem->id;
                $userData['user_name'] = $userItem->name ?? "用户_{$userItem->id}";
                $userData['user_email'] = $userItem->email;
                $userData['type'] = 'user';

                $dayUsers[] = $userData;
            }

            // 计算others数据（all - users）
            $allUsersAccountIds = array_unique($allUsersAccountIds);
            $othersAccountIds = array_diff($allFbAdAccountSourceIds, $allUsersAccountIds);

            $othersData = $this->calculateInsightsData($othersAccountIds, $currentDate, $currentDate);
            $othersData['user_id'] = null;
            $othersData['user_name'] = 'Others';
            $othersData['user_email'] = null;
            $othersData['type'] = 'others';

            // 将others数据添加到用户列表中
            $dayUsers[] = $othersData;

            // 按该天的消费倒序排列（包含others）
            usort($dayUsers, function ($a, $b) {
                return $b['spend'] <=> $a['spend'];
            });

            $trendsData[] = [
                'date' => $currentDate,
                'users' => $dayUsers
            ];
        }

        // 🎯 计算所有广告账户的汇总数据（与trends接口保持一致）
        // 按44.1.2和44.2.2的处理方式：Admin查看所有FbAdAccount
        $allFbAdAccountsQuery = FbAdAccount::query();
        if (!empty($excludeSourceIds)) {
            $allFbAdAccountsQuery->whereNotIn('source_id', $excludeSourceIds);
        }
        $allFbAdAccountSourceIds = $allFbAdAccountsQuery->pluck('source_id')->toArray();

        $summaryData = $this->calculateInsightsData($allFbAdAccountSourceIds, $date_start, $date_stop);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => array_merge($summaryData, [
                    'date_start' => $date_start,
                    'date_stop' => $date_stop
                ]),
                'trends' => $trendsData,
                'total_days' => count($trendsData),
                'total_users' => count($users)
            ]
        ]);
    }

    /**
     * 获取按广告账户分组的趋势数据
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trendsAdAccounts(Request $request)
    {
        $validatedData = $request->validate([
            'date_start' => 'required|date_format:Y-m-d',
            'date_stop' => 'required|date_format:Y-m-d|after_or_equal:date_start'
        ]);

        $date_start = $validatedData['date_start'];
        $date_stop = $validatedData['date_stop'];
        $excludeSourceIds = config('insights-config.excluded_ad_account_source_ids', []);
        $user = $request->user();

        // 根据用户权限获取FbAdAccount
        if ($user->hasRole('admin')) {
            $fbAdAccountsQuery = FbAdAccount::query();
            if (!empty($excludeSourceIds)) {
                $fbAdAccountsQuery->whereNotIn('source_id', $excludeSourceIds);
            }
            $fbAdAccounts = $fbAdAccountsQuery->with(['users'])->get();
        } else {
            $fbAdAccounts = $user->fbAdAccounts()->with(['users'])->get();
            if (!empty($excludeSourceIds)) {
                $fbAdAccounts = $fbAdAccounts->whereNotIn('source_id', $excludeSourceIds);
            }
        }

        // 生成日期范围内的每一天
        $startDate = Carbon::createFromFormat('Y-m-d', $date_start);
        $endDate = Carbon::createFromFormat('Y-m-d', $date_stop);
        $dateRange = [];

        while ($startDate <= $endDate) {
            $dateRange[] = $startDate->format('Y-m-d');
            $startDate->addDay();
        }

        // 为每一天每个广告账户计算统计数据
        $trendsData = [];
        foreach ($dateRange as $currentDate) {
            $dayAccounts = [];

            foreach ($fbAdAccounts as $fbAdAccount) {
                $accountData = $this->calculateInsightsData([$fbAdAccount->source_id], $currentDate, $currentDate);

                // 只包含有消费的广告账户
                if ($accountData['spend'] > 0) {
                    $accountData['account_id'] = $fbAdAccount->source_id;
                    $accountData['account_name'] = $fbAdAccount->name;
                    $accountData['account_status'] = $fbAdAccount->account_status;
                    $accountData['currency'] = $fbAdAccount->currency;
                    $accountData['default_funding'] = $fbAdAccount->default_funding;

                    // 如果有关联用户，返回用户信息
                    if ($fbAdAccount->users && $fbAdAccount->users->count() > 0) {
                        $accountData['users'] = $fbAdAccount->users->map(function ($user) {
                            return [
                                'user_id' => $user->id,
                                'name' => $user->name,
                                'email' => $user->email
                            ];
                        })->toArray();
                    } else {
                        $accountData['users'] = [];
                    }

                    $dayAccounts[] = $accountData;
                }
            }

            // 按该天的消费倒序排列账户
            usort($dayAccounts, function ($a, $b) {
                return $b['spend'] <=> $a['spend'];
            });

            $trendsData[] = [
                'date' => $currentDate,
                'ad_accounts' => $dayAccounts
            ];
        }

        // 计算汇总数据
        $allAccountSourceIds = $fbAdAccounts->pluck('source_id')->toArray();
        $summaryData = $this->calculateInsightsData($allAccountSourceIds, $date_start, $date_stop);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => array_merge($summaryData, [
                    'date_start' => $date_start,
                    'date_stop' => $date_stop
                ]),
                'trends' => $trendsData,
                'total_days' => count($trendsData)
            ]
        ]);
    }

    /**
     * 获取按Campaign分组的趋势数据
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trendsCampaigns(Request $request)
    {
        $validatedData = $request->validate([
            'date_start' => 'required|date_format:Y-m-d',
            'date_stop' => 'required|date_format:Y-m-d|after_or_equal:date_start'
        ]);

        $date_start = $validatedData['date_start'];
        $date_stop = $validatedData['date_stop'];
        $excludeSourceIds = config('insights-config.excluded_ad_account_source_ids', []);
        $user = $request->user();

        // 根据用户权限获取FbAdAccount
        if ($user->hasRole('admin')) {
            $fbAdAccountsQuery = FbAdAccount::query();
            if (!empty($excludeSourceIds)) {
                $fbAdAccountsQuery->whereNotIn('source_id', $excludeSourceIds);
            }
            $fbAdAccounts = $fbAdAccountsQuery->get();
        } else {
            $fbAdAccounts = $user->fbAdAccounts;
            if (!empty($excludeSourceIds)) {
                $fbAdAccounts = $fbAdAccounts->whereNotIn('source_id', $excludeSourceIds);
            }
        }

        // 获取有权限的Campaign
        $fbCampaigns = FbCampaign::whereHas('fbAdAccount', function($query) use ($fbAdAccounts) {
                $query->whereIn('id', $fbAdAccounts->pluck('id'));
            })
            ->with(['fbAdAccount.users', 'insights' => function($query) use ($date_start, $date_stop) {
                $query->whereBetween('date_start', [$date_start, $date_stop]);
            }])
            ->get();

        // 生成日期范围内的每一天
        $startDate = Carbon::createFromFormat('Y-m-d', $date_start);
        $endDate = Carbon::createFromFormat('Y-m-d', $date_stop);
        $dateRange = [];

        while ($startDate <= $endDate) {
            $dateRange[] = $startDate->format('Y-m-d');
            $startDate->addDay();
        }

        // 为每一天每个Campaign计算统计数据
        $trendsData = [];
        foreach ($dateRange as $currentDate) {
            $dayCampaigns = [];

            foreach ($fbCampaigns as $fbCampaign) {
                $campaignData = $this->calculateCampaignInsightsData($fbCampaign, $currentDate, $currentDate);

                // 只包含有消费的Campaign
                if ($campaignData['spend'] > 0) {
                    // 添加广告账户信息
                    $campaignData['ad_account_id'] = $fbCampaign->fbAdAccount->source_id;
                    $campaignData['ad_account_name'] = $fbCampaign->fbAdAccount->name;
                    $campaignData['account_status'] = $fbCampaign->fbAdAccount->account_status;
                    $campaignData['currency'] = $fbCampaign->fbAdAccount->currency;
                    $campaignData['default_funding'] = $fbCampaign->fbAdAccount->default_funding;

                    // 如果有关联用户，返回用户信息
                    if ($fbCampaign->fbAdAccount->users && $fbCampaign->fbAdAccount->users->count() > 0) {
                        $campaignData['users'] = $fbCampaign->fbAdAccount->users->map(function ($user) {
                            return [
                                'user_id' => $user->id,
                                'name' => $user->name,
                                'email' => $user->email
                            ];
                        })->toArray();
                    } else {
                        $campaignData['users'] = [];
                    }

                    $dayCampaigns[] = $campaignData;
                }
            }

            // 按该天的消费倒序排列Campaign
            usort($dayCampaigns, function ($a, $b) {
                return $b['spend'] <=> $a['spend'];
            });

            $trendsData[] = [
                'date' => $currentDate,
                'campaigns' => $dayCampaigns
            ];
        }

        // 计算汇总数据
        $allCampaignSourceIds = $fbCampaigns->pluck('source_id')->toArray();
        $summaryData = $this->calculateCampaignsSummaryData($allCampaignSourceIds, $date_start, $date_stop);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => array_merge($summaryData, [
                    'date_start' => $date_start,
                    'date_stop' => $date_stop
                ]),
                'trends' => $trendsData,
                'total_days' => count($trendsData)
            ]
        ]);
    }

    /**
     * 获取按Adset分组的趋势数据
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trendsAdsets(Request $request)
    {
        $validatedData = $request->validate([
            'date_start' => 'required|date_format:Y-m-d',
            'date_stop' => 'required|date_format:Y-m-d|after_or_equal:date_start'
        ]);

        $date_start = $validatedData['date_start'];
        $date_stop = $validatedData['date_stop'];
        $excludeSourceIds = config('insights-config.excluded_ad_account_source_ids', []);
        $user = $request->user();

        // 根据用户权限获取FbAdAccount
        if ($user->hasRole('admin')) {
            $fbAdAccountsQuery = FbAdAccount::query();
            if (!empty($excludeSourceIds)) {
                $fbAdAccountsQuery->whereNotIn('source_id', $excludeSourceIds);
            }
            $fbAdAccounts = $fbAdAccountsQuery->get();
        } else {
            $fbAdAccounts = $user->fbAdAccounts;
            if (!empty($excludeSourceIds)) {
                $fbAdAccounts = $fbAdAccounts->whereNotIn('source_id', $excludeSourceIds);
            }
        }

        // 获取有权限的Adset
        $fbAdsets = FbAdset::whereHas('fbAdAccount', function($query) use ($fbAdAccounts) {
                $query->whereIn('id', $fbAdAccounts->pluck('id'));
            })
            ->with(['fbAdAccount.users', 'fbCampaign', 'insights' => function($query) use ($date_start, $date_stop) {
                $query->whereBetween('date_start', [$date_start, $date_stop]);
            }])
            ->get();

        // 生成日期范围内的每一天
        $startDate = Carbon::createFromFormat('Y-m-d', $date_start);
        $endDate = Carbon::createFromFormat('Y-m-d', $date_stop);
        $dateRange = [];

        while ($startDate <= $endDate) {
            $dateRange[] = $startDate->format('Y-m-d');
            $startDate->addDay();
        }

        // 为每一天每个Adset计算统计数据
        $trendsData = [];
        foreach ($dateRange as $currentDate) {
            $dayAdsets = [];

            foreach ($fbAdsets as $fbAdset) {
                $adsetData = $this->calculateAdsetInsightsData($fbAdset, $currentDate, $currentDate);

                // 只包含有消费的Adset
                if ($adsetData['spend'] > 0) {
                    // 添加广告账户信息
                    $adsetData['ad_account_id'] = $fbAdset->fbAdAccount->source_id;
                    $adsetData['ad_account_name'] = $fbAdset->fbAdAccount->name;
                    $adsetData['account_status'] = $fbAdset->fbAdAccount->account_status;
                    $adsetData['currency'] = $fbAdset->fbAdAccount->currency;
                    $adsetData['default_funding'] = $fbAdset->fbAdAccount->default_funding;

                    // 如果有关联用户，返回用户信息
                    if ($fbAdset->fbAdAccount->users && $fbAdset->fbAdAccount->users->count() > 0) {
                        $adsetData['users'] = $fbAdset->fbAdAccount->users->map(function ($user) {
                            return [
                                'user_id' => $user->id,
                                'name' => $user->name,
                                'email' => $user->email
                            ];
                        })->toArray();
                    } else {
                        $adsetData['users'] = [];
                    }

                    $dayAdsets[] = $adsetData;
                }
            }

            // 按该天的消费倒序排列Adset
            usort($dayAdsets, function ($a, $b) {
                return $b['spend'] <=> $a['spend'];
            });

            $trendsData[] = [
                'date' => $currentDate,
                'adsets' => $dayAdsets
            ];
        }

        // 计算汇总数据
        $allAdsetSourceIds = $fbAdsets->pluck('source_id')->toArray();
        $summaryData = $this->calculateAdsetsSummaryData($allAdsetSourceIds, $date_start, $date_stop);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => array_merge($summaryData, [
                    'date_start' => $date_start,
                    'date_stop' => $date_stop
                ]),
                'trends' => $trendsData,
                'total_days' => count($trendsData)
            ]
        ]);
    }

    /**
     * 获取按Offer分组的趋势数据
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trendsOffers(Request $request)
    {
        $validatedData = $request->validate([
            'date_start' => 'required|date_format:Y-m-d',
            'date_stop' => 'required|date_format:Y-m-d|after_or_equal:date_start'
        ]);

        $date_start = $validatedData['date_start'];
        $date_stop = $validatedData['date_stop'];
        $excludeSourceIds = config('insights-config.excluded_ad_account_source_ids', []);
        $user = $request->user();

        // 根据用户权限获取FbAdAccount
        if ($user->hasRole('admin')) {
            $fbAdAccountsQuery = FbAdAccount::query();
            if (!empty($excludeSourceIds)) {
                $fbAdAccountsQuery->whereNotIn('source_id', $excludeSourceIds);
            }
            $fbAdAccounts = $fbAdAccountsQuery->get();
        } else {
            $fbAdAccounts = $user->fbAdAccounts;
            if (!empty($excludeSourceIds)) {
                $fbAdAccounts = $fbAdAccounts->whereNotIn('source_id', $excludeSourceIds);
            }
        }

        // 获取有权限的Campaign source_ids
        $campaignSourceIds = FbCampaign::whereHas('fbAdAccount', function($query) use ($fbAdAccounts) {
                $query->whereIn('id', $fbAdAccounts->pluck('id'));
            })
            ->pluck('source_id')
            ->toArray();

        // 生成日期范围内的每一天
        $startDate = Carbon::createFromFormat('Y-m-d', $date_start);
        $endDate = Carbon::createFromFormat('Y-m-d', $date_stop);
        $dateRange = [];

        while ($startDate <= $endDate) {
            $dateRange[] = $startDate->format('Y-m-d');
            $startDate->addDay();
        }

        // 为每一天按Offer计算统计数据
        $trendsData = [];
        foreach ($dateRange as $currentDate) {
            $dayOffers = [];

            if (!empty($campaignSourceIds)) {
                // 🎯 按时区分组获取该天的Conversion数据
                $campaigns = FbCampaign::whereIn('source_id', $campaignSourceIds)
                    ->with('fbAdAccount:id,source_id,timezone_name')
                    ->get(['source_id', 'fb_ad_account_id']);

                $campaignsByTimezone = $campaigns->groupBy('fbAdAccount.timezone_name');
                $allDayConversions = collect();

                foreach ($campaignsByTimezone as $timezone => $campaignsGroup) {
                    $timezoneCampaignIds = $campaignsGroup->pluck('source_id')->toArray();

                    // 计算该时区的时间范围
                    $timezoneToUse = $timezone ?? 'UTC';
                    $startDateInTimeZone = Carbon::createFromFormat('Y-m-d', $currentDate, $timezoneToUse)->startOfDay()->setTimezone('UTC');
                    $endDateInTimeZone = Carbon::createFromFormat('Y-m-d', $currentDate, $timezoneToUse)->endOfDay()->setTimezone('UTC');

                    // 获取该时区该天的Conversion数据
                    $timezoneConversions = Conversion::whereIn('fb_campaign_source_id', $timezoneCampaignIds)
                        ->whereBetween('conversion_datetime', [$startDateInTimeZone, $endDateInTimeZone])
                        ->whereNotNull('offer_source_name')
                        ->where('offer_source_name', '!=', '')
                        ->get();

                    $allDayConversions = $allDayConversions->merge($timezoneConversions);
                }

                // 按offer_source_name分组
                $offersGrouped = $allDayConversions->groupBy('offer_source_name');

                foreach ($offersGrouped as $offerName => $offerConversions) {
                    $offerData = $this->calculateOfferInsightsData($offerConversions, $offerName, $currentDate, $currentDate);

                    // 只包含有数据的Offer
                    if ($offerData['leads'] > 0 || $offerData['sales'] > 0 || $offerData['spend'] > 0) {
                        $dayOffers[] = $offerData;
                    }
                }
            }

            // 按该天的收入倒序排列Offer
            usort($dayOffers, function ($a, $b) {
                return $b['revenue'] <=> $a['revenue'];
            });

            $trendsData[] = [
                'date' => $currentDate,
                'offers' => $dayOffers
            ];
        }

        // 计算汇总数据
        $allOfferNames = [];
        foreach ($trendsData as $dayData) {
            foreach ($dayData['offers'] as $offer) {
                $allOfferNames[] = $offer['offer_name'];
            }
        }
        $allOfferNames = array_unique($allOfferNames);

        $summaryData = $this->calculateOffersSummaryData($allOfferNames, $campaignSourceIds, $date_start, $date_stop);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => array_merge($summaryData, [
                    'date_start' => $date_start,
                    'date_stop' => $date_stop
                ]),
                'trends' => $trendsData,
                'total_days' => count($trendsData)
            ]
        ]);
    }

    /**
     * 获取按Campaign Tag分组的统计概览
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function overviewCampaignTags(Request $request)
    {
        $validatedData = $request->validate([
            'date_start' => 'required|date_format:Y-m-d',
            'date_stop' => 'required|date_format:Y-m-d|after_or_equal:date_start'
        ]);

        $date_start = $validatedData['date_start'];
        $date_stop = $validatedData['date_stop'];
        $excludeSourceIds = config('insights-config.excluded_ad_account_source_ids', []);
        $user = $request->user();

        // 获取当前用户有权限查看的广告账户ID
        if ($user->hasRole('admin')) {
            $allowedAdAccountIds = FbAdAccount::query();
            if (!empty($excludeSourceIds)) {
                $allowedAdAccountIds->whereNotIn('source_id', $excludeSourceIds);
            }
            $allowedAdAccountIds = $allowedAdAccountIds->pluck('id')->toArray();
        } else {
            $userAdAccounts = $user->fbAdAccounts;
            if (!empty($excludeSourceIds)) {
                $userAdAccounts = $userAdAccounts->whereNotIn('source_id', $excludeSourceIds);
            }
            $allowedAdAccountIds = $userAdAccounts->pluck('id')->toArray();
        }

        // 🚀 性能优化：先找出有消费的Campaign，再按tag分组
        $campaignsWithSpend = FbCampaignInsight::join('fb_campaigns', 'fb_campaign_insights.campaign_id', '=', 'fb_campaigns.source_id')
            ->whereIn('fb_campaigns.fb_ad_account_id', $allowedAdAccountIds)
            ->whereBetween('fb_campaign_insights.date_start', [$date_start, $date_stop])
            ->where('fb_campaign_insights.spend', '>', 0)
            ->distinct()
            ->pluck('fb_campaigns.source_id')
            ->toArray();

        if (empty($campaignsWithSpend)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'spend' => 0, 'link_clicks' => 0, 'offer_clicks' => 0,
                        'leads' => 0, 'sales' => 0, 'revenue' => 0,
                        'profit' => 0, 'roi' => 0, 'campaigns_count' => 0,
                        'date_start' => $date_start, 'date_stop' => $date_stop
                    ],
                    'tags' => [],
                    'total_tags' => 0
                ]
            ]);
        }

        // 获取有消费的Campaign关联的用户tags
        $userTagsWithSpend = Tag::where('user_id', $user->id)
            ->whereHas('fbCampaigns', function($query) use ($campaignsWithSpend) {
                $query->whereIn('source_id', $campaignsWithSpend);
            })
            ->get();

        $tagsData = [];
        $allTaggedCampaignIds = [];

        foreach ($userTagsWithSpend as $tag) {
            // 获取该tag下有消费的Campaign
            $tagCampaignSourceIds = $tag->fbCampaigns()
                ->whereIn('source_id', $campaignsWithSpend)
                ->pluck('source_id')
                ->toArray();

            if (!empty($tagCampaignSourceIds)) {
                $allTaggedCampaignIds = array_merge($allTaggedCampaignIds, $tagCampaignSourceIds);

                // 计算该tag的统计数据
                $tagData = $this->calculateCampaignsSummaryData($tagCampaignSourceIds, $date_start, $date_stop);

                // 添加tag基本信息
                $tagData['tag_id'] = $tag->id;
                $tagData['tag_name'] = $tag->name;

                $tagsData[] = $tagData;
            }
        }

        // 按收入倒序排列
        usort($tagsData, function ($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });

        // 计算汇总数据
        $allTaggedCampaignIds = array_unique($allTaggedCampaignIds);
        $summaryData = $this->calculateCampaignsSummaryData($allTaggedCampaignIds, $date_start, $date_stop);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => array_merge($summaryData, [
                    'date_start' => $date_start,
                    'date_stop' => $date_stop
                ]),
                'tags' => $tagsData,
                'total_tags' => count($tagsData)
            ]
        ]);
    }

    /**
     * 计算单个Offer的统计数据（考虑时区）
     */
    private function calculateOfferInsightsData($offerConversions, $offerName, $date_start, $date_stop)
    {
        $leads = $offerConversions->where('price', '=', 0)->count();
        $sales = $offerConversions->where('price', '>', 0)->count();
        $revenue = $offerConversions->where('price', '>', 0)->sum('price');

        // 获取该offer相关的campaigns
        $campaignsUsedByOffer = $offerConversions->pluck('fb_campaign_source_id')->unique()->toArray();

        $spend = 0;
        $linkClicks = 0;
        $offerClicks = 0;

        if (!empty($campaignsUsedByOffer)) {
            // 获取该offer相关的spend和link_clicks数据（只从相关campaigns）
            $insightsData = FbCampaignInsight::whereIn('campaign_id', $campaignsUsedByOffer)
                ->whereBetween('date_start', [$date_start, $date_stop])
                ->selectRaw('SUM(spend) as total_spend, SUM(inline_link_clicks) as total_link_clicks')
                ->first();

            $spend = $insightsData->total_spend ?? 0;
            $linkClicks = $insightsData->total_link_clicks ?? 0;

            // 🎯 按时区分组计算offer clicks（针对该offer的campaigns）
            $campaigns = FbCampaign::whereIn('source_id', $campaignsUsedByOffer)
                ->with('fbAdAccount:id,source_id,timezone_name')
                ->get(['source_id', 'fb_ad_account_id']);

            $campaignsByTimezone = $campaigns->groupBy('fbAdAccount.timezone_name');

            foreach ($campaignsByTimezone as $timezone => $campaignsGroup) {
                $timezoneCampaignIds = $campaignsGroup->pluck('source_id')->toArray();

                // 计算该时区的时间范围
                $timezoneToUse = $timezone ?? 'UTC';
                $tzStartDate = Carbon::createFromFormat('Y-m-d', $date_start, $timezoneToUse)->startOfDay()->setTimezone('UTC');
                $tzEndDate = Carbon::createFromFormat('Y-m-d', $date_stop, $timezoneToUse)->endOfDay()->setTimezone('UTC');

                // 查询该时区下的offer clicks
                $timezoneOfferClicks = Click::whereIn('fb_campaign_source_id', $timezoneCampaignIds)
                    ->whereBetween('click_datetime', [$tzStartDate, $tzEndDate])
                    ->count();

                $offerClicks += $timezoneOfferClicks;
            }
        }

        $profit = round($revenue - $spend, 2);
        $roi = ($spend != 0) ? round($profit / $spend, 4) : 0;

        return [
            'offer_name' => $offerName,
            'offer_source_id' => $offerConversions->first()->offer_source_id ?? '',
            'spend' => round($spend, 2),
            'link_clicks' => (int)$linkClicks,
            'offer_clicks' => $offerClicks,
            'leads' => $leads,
            'sales' => $sales,
            'revenue' => round($revenue, 2),
            'profit' => $profit,
            'roi' => $roi,
            'campaigns_count' => count($campaignsUsedByOffer),
            'conversions_count' => $offerConversions->count()
        ];
    }


    /**
     * 计算Offers汇总数据（考虑时区）
     */
    private function calculateOffersSummaryData($offerNames, $campaignSourceIds, $date_start, $date_stop)
    {
        if (empty($campaignSourceIds)) {
            return [
                'spend' => 0, 'link_clicks' => 0, 'offer_clicks' => 0,
                'leads' => 0, 'sales' => 0, 'revenue' => 0,
                'profit' => 0, 'roi' => 0, 'offers_count' => 0
            ];
        }

        // 获取所有相关campaigns的spend数据
        $insightsData = FbCampaignInsight::whereIn('campaign_id', $campaignSourceIds)
            ->whereBetween('date_start', [$date_start, $date_stop])
            ->selectRaw('SUM(spend) as total_spend, SUM(inline_link_clicks) as total_link_clicks')
            ->first();

        $totalSpend = $insightsData->total_spend ?? 0;
        $totalLinkClicks = $insightsData->total_link_clicks ?? 0;

        $totalOfferClicks = 0;
        $totalLeads = 0;
        $totalSales = 0;
        $totalRevenue = 0;

        // 🎯 按时区分组计算offer数据
        $campaigns = FbCampaign::whereIn('source_id', $campaignSourceIds)
            ->with('fbAdAccount:id,source_id,timezone_name')
            ->get(['source_id', 'fb_ad_account_id']);

        $campaignsByTimezone = $campaigns->groupBy('fbAdAccount.timezone_name');

        foreach ($campaignsByTimezone as $timezone => $campaignsGroup) {
            $timezoneCampaignIds = $campaignsGroup->pluck('source_id')->toArray();

            // 计算该时区的时间范围
            $timezoneToUse = $timezone ?? 'UTC';
            $startDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_start, $timezoneToUse)->startOfDay()->setTimezone('UTC');
            $endDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_stop, $timezoneToUse)->endOfDay()->setTimezone('UTC');

            // 查询该时区下的offer clicks
            $timezoneOfferClicks = Click::whereIn('fb_campaign_source_id', $timezoneCampaignIds)
                ->whereBetween('click_datetime', [$startDateInTimeZone, $endDateInTimeZone])
                ->count();

            $totalOfferClicks += $timezoneOfferClicks;

            // 查询该时区下的offer conversions
            $timezoneConversionData = Conversion::whereIn('fb_campaign_source_id', $timezoneCampaignIds)
                ->whereBetween('conversion_datetime', [$startDateInTimeZone, $endDateInTimeZone])
                ->whereNotNull('offer_source_name')
                ->where('offer_source_name', '!=', '')
                ->selectRaw('
                    SUM(CASE WHEN price = 0 THEN 1 ELSE 0 END) as total_leads,
                    SUM(CASE WHEN price > 0 THEN 1 ELSE 0 END) as total_sales,
                    SUM(CASE WHEN price > 0 THEN price ELSE 0 END) as total_revenue
                ')
                ->first();

            $totalLeads += $timezoneConversionData->total_leads ?? 0;
            $totalSales += $timezoneConversionData->total_sales ?? 0;
            $totalRevenue += $timezoneConversionData->total_revenue ?? 0;
        }

        $totalProfit = round($totalRevenue - $totalSpend, 2);
        $roi = ($totalSpend != 0) ? round($totalProfit / $totalSpend, 4) : 0;

        return [
            'spend' => round($totalSpend, 2),
            'link_clicks' => (int)$totalLinkClicks,
            'offer_clicks' => $totalOfferClicks,
            'leads' => (int)$totalLeads,
            'sales' => (int)$totalSales,
            'revenue' => round($totalRevenue, 2),
            'profit' => $totalProfit,
            'roi' => $roi,
            'offers_count' => count($offerNames)
        ];
    }

    /**
     * 计算单个Campaign的统计数据
     */
    private function calculateCampaignInsightsData($fbCampaign, $date_start, $date_stop)
    {
        // 设置时区和时间范围
        $timezone = $fbCampaign->fbAdAccount->timezone_name ?? 'UTC';
        $startDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_start, $timezone)->startOfDay()->setTimezone('UTC');
        $endDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_stop, $timezone)->endOfDay()->setTimezone('UTC');

        // 获取offer相关数据
        $offerClicksCount = $fbCampaign->offerClicks()->whereBetween('click_datetime', [$startDateInTimeZone, $endDateInTimeZone])->count();
        $offerConversionQuery = $fbCampaign->offerConversions()->whereBetween('conversion_datetime', [$startDateInTimeZone, $endDateInTimeZone]);

        $offerConversionsCount = (clone $offerConversionQuery)->where('price', '>', 0)->count();
        $offerConversionsValue = (clone $offerConversionQuery)->where('price', '>', 0)->sum('price');
        $offerLeads = (clone $offerConversionQuery)->where('price', '=', 0)->count();

        $spend = $fbCampaign->insights->sum('spend');
        $profit = round(($offerConversionsValue - $spend), 2);
        $roi = ($spend != 0) ? round($profit / $spend, 4) : 0;

        return [
            'campaign_id' => $fbCampaign->source_id,
            'campaign_name' => $fbCampaign->name,
            'campaign_status' => $fbCampaign->status,
            'effective_status' => $fbCampaign->effective_status,
            'daily_budget' => $fbCampaign->daily_budget,
            'lifetime_budget' => $fbCampaign->lifetime_budget,
            'spend' => round($spend, 2),
            'link_clicks' => $fbCampaign->insights->sum('inline_link_clicks'),
            'offer_clicks' => $offerClicksCount,
            'leads' => $offerLeads,
            'sales' => $offerConversionsCount,
            'revenue' => round($offerConversionsValue, 2),
            'profit' => $profit,
            'roi' => $roi
        ];
    }

    /**
     * 计算单个Adset的统计数据
     */
    private function calculateAdsetInsightsData($fbAdset, $date_start, $date_stop)
    {
        // 设置时区和时间范围
        $timezone = $fbAdset->fbAdAccount->timezone_name ?? 'UTC';
        $startDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_start, $timezone)->startOfDay()->setTimezone('UTC');
        $endDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_stop, $timezone)->endOfDay()->setTimezone('UTC');

        // 获取offer相关数据
        $offerClicksCount = $fbAdset->offerClicks()->whereBetween('click_datetime', [$startDateInTimeZone, $endDateInTimeZone])->count();
        $offerConversionQuery = $fbAdset->offerConversions()->whereBetween('conversion_datetime', [$startDateInTimeZone, $endDateInTimeZone]);

        $offerConversionsCount = (clone $offerConversionQuery)->where('price', '>', 0)->count();
        $offerConversionsValue = (clone $offerConversionQuery)->where('price', '>', 0)->sum('price');
        $offerLeads = (clone $offerConversionQuery)->where('price', '=', 0)->count();

        $spend = $fbAdset->insights->sum('spend');
        $profit = round(($offerConversionsValue - $spend), 2);
        $roi = ($spend != 0) ? round($profit / $spend, 4) : 0;

        return [
            'adset_id' => $fbAdset->source_id,
            'adset_name' => $fbAdset->name,
            'adset_status' => $fbAdset->status,
            'effective_status' => $fbAdset->effective_status,
            'daily_budget' => $fbAdset->daily_budget,
            'lifetime_budget' => $fbAdset->lifetime_budget,
            'campaign_id' => $fbAdset->fbCampaign->source_id,
            'campaign_name' => $fbAdset->fbCampaign->name,
            'spend' => round($spend, 2),
            'link_clicks' => $fbAdset->insights->sum('inline_link_clicks'),
            'offer_clicks' => $offerClicksCount,
            'leads' => $offerLeads,
            'sales' => $offerConversionsCount,
            'revenue' => round($offerConversionsValue, 2),
            'profit' => $profit,
            'roi' => $roi
        ];
    }

    /**
     * 计算Campaigns汇总数据（考虑时区）
     */
    private function calculateCampaignsSummaryData($campaignSourceIds, $date_start, $date_stop)
    {
        if (empty($campaignSourceIds)) {
            return [
                'spend' => 0, 'link_clicks' => 0, 'offer_clicks' => 0,
                'leads' => 0, 'sales' => 0, 'revenue' => 0,
                'profit' => 0, 'roi' => 0, 'campaigns_count' => 0
            ];
        }

        $insightsData = FbCampaignInsight::whereIn('campaign_id', $campaignSourceIds)
            ->whereBetween('date_start', [$date_start, $date_stop])
            ->selectRaw('SUM(spend) as total_spend, SUM(inline_link_clicks) as total_link_clicks')
            ->first();

        $totalSpend = $insightsData->total_spend ?? 0;
        $totalLinkClicks = $insightsData->total_link_clicks ?? 0;

        $totalOfferClicks = 0;
        $totalOfferLeads = 0;
        $totalOfferConversions = 0;
        $totalRevenue = 0;

        // 🎯 按时区分组计算offer数据
        $campaigns = FbCampaign::whereIn('source_id', $campaignSourceIds)
            ->with('fbAdAccount:id,source_id,timezone_name')
            ->get(['source_id', 'fb_ad_account_id']);

        $campaignsByTimezone = $campaigns->groupBy('fbAdAccount.timezone_name');

        foreach ($campaignsByTimezone as $timezone => $campaignsGroup) {
            $timezoneCampaignIds = $campaignsGroup->pluck('source_id')->toArray();

            // 计算该时区的时间范围
            $timezoneToUse = $timezone ?? 'UTC';
            $startDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_start, $timezoneToUse)->startOfDay()->setTimezone('UTC');
            $endDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_stop, $timezoneToUse)->endOfDay()->setTimezone('UTC');

            // 查询该时区下的offer clicks
            $timezoneOfferClicks = Click::whereIn('fb_campaign_source_id', $timezoneCampaignIds)
                ->whereBetween('click_datetime', [$startDateInTimeZone, $endDateInTimeZone])
                ->count();

            $totalOfferClicks += $timezoneOfferClicks;

            // 查询该时区下的offer conversions
            $timezoneConversionData = Conversion::whereIn('fb_campaign_source_id', $timezoneCampaignIds)
                ->whereBetween('conversion_datetime', [$startDateInTimeZone, $endDateInTimeZone])
                ->selectRaw('
                    SUM(CASE WHEN price = 0 THEN 1 ELSE 0 END) as total_leads,
                    SUM(CASE WHEN price > 0 THEN 1 ELSE 0 END) as total_conversions,
                    SUM(CASE WHEN price > 0 THEN price ELSE 0 END) as total_revenue
                ')
                ->first();

            $totalOfferLeads += $timezoneConversionData->total_leads ?? 0;
            $totalOfferConversions += $timezoneConversionData->total_conversions ?? 0;
            $totalRevenue += $timezoneConversionData->total_revenue ?? 0;
        }

        $totalProfit = round($totalRevenue - $totalSpend, 2);
        $roi = ($totalSpend != 0) ? round($totalProfit / $totalSpend, 4) : 0;

        return [
            'spend' => round($totalSpend, 2),
            'link_clicks' => (int)$totalLinkClicks,
            'offer_clicks' => $totalOfferClicks,
            'leads' => (int)$totalOfferLeads,
            'sales' => (int)$totalOfferConversions,
            'revenue' => round($totalRevenue, 2),
            'profit' => $totalProfit,
            'roi' => $roi,
            'campaigns_count' => count($campaignSourceIds)
        ];
    }

    /**
     * 计算Adsets汇总数据（考虑时区）
     */
    private function calculateAdsetsSummaryData($adsetSourceIds, $date_start, $date_stop)
    {
        if (empty($adsetSourceIds)) {
            return [
                'spend' => 0, 'link_clicks' => 0, 'offer_clicks' => 0,
                'leads' => 0, 'sales' => 0, 'revenue' => 0,
                'profit' => 0, 'roi' => 0, 'adsets_count' => 0
            ];
        }

        $insightsData = FbAdsetInsight::whereIn('adset_id', $adsetSourceIds)
            ->whereBetween('date_start', [$date_start, $date_stop])
            ->selectRaw('SUM(spend) as total_spend, SUM(inline_link_clicks) as total_link_clicks')
            ->first();

        $totalSpend = $insightsData->total_spend ?? 0;
        $totalLinkClicks = $insightsData->total_link_clicks ?? 0;

        $totalOfferClicks = 0;
        $totalOfferLeads = 0;
        $totalOfferConversions = 0;
        $totalRevenue = 0;

        // 🎯 按时区分组计算offer数据
        $adsets = FbAdset::whereIn('source_id', $adsetSourceIds)
            ->with('fbAdAccount:id,source_id,timezone_name')
            ->get(['source_id', 'account_id']);

        $adsetsByTimezone = $adsets->groupBy('fbAdAccount.timezone_name');

        foreach ($adsetsByTimezone as $timezone => $adsetsGroup) {
            $timezoneAdsetIds = $adsetsGroup->pluck('source_id')->toArray();

            // 计算该时区的时间范围
            $timezoneToUse = $timezone ?? 'UTC';
            $startDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_start, $timezoneToUse)->startOfDay()->setTimezone('UTC');
            $endDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_stop, $timezoneToUse)->endOfDay()->setTimezone('UTC');

            // 查询该时区下的offer clicks
            $timezoneOfferClicks = Click::whereIn('fb_adset_source_id', $timezoneAdsetIds)
                ->whereBetween('click_datetime', [$startDateInTimeZone, $endDateInTimeZone])
                ->count();

            $totalOfferClicks += $timezoneOfferClicks;

            // 查询该时区下的offer conversions
            $timezoneConversionData = Conversion::whereIn('fb_adset_source_id', $timezoneAdsetIds)
                ->whereBetween('conversion_datetime', [$startDateInTimeZone, $endDateInTimeZone])
                ->selectRaw('
                    SUM(CASE WHEN price = 0 THEN 1 ELSE 0 END) as total_leads,
                    SUM(CASE WHEN price > 0 THEN 1 ELSE 0 END) as total_conversions,
                    SUM(CASE WHEN price > 0 THEN price ELSE 0 END) as total_revenue
                ')
                ->first();

            $totalOfferLeads += $timezoneConversionData->total_leads ?? 0;
            $totalOfferConversions += $timezoneConversionData->total_conversions ?? 0;
            $totalRevenue += $timezoneConversionData->total_revenue ?? 0;
        }

        $totalProfit = round($totalRevenue - $totalSpend, 2);
        $roi = ($totalSpend != 0) ? round($totalProfit / $totalSpend, 4) : 0;

        return [
            'spend' => round($totalSpend, 2),
            'link_clicks' => (int)$totalLinkClicks,
            'offer_clicks' => $totalOfferClicks,
            'leads' => (int)$totalOfferLeads,
            'sales' => (int)$totalOfferConversions,
            'revenue' => round($totalRevenue, 2),
            'profit' => $totalProfit,
            'roi' => $roi,
            'adsets_count' => count($adsetSourceIds)
        ];
    }

    /**
     * 计算给定广告账户列表的统计数据（考虑时区）
     * @param array $accountSourceIds
     * @param string $date_start
     * @param string $date_stop
     * @return array
     */
    private function calculateInsightsData(array $accountSourceIds, string $date_start, string $date_stop): array
    {
        if (empty($accountSourceIds)) {
            return [
                'spend' => 0,
                'link_clicks' => 0,
                'offer_clicks' => 0,
                'leads' => 0,
                'sales' => 0,
                'revenue' => 0,
                'profit' => 0,
                'roi' => 0,
                'ad_accounts_count' => 0,
                'accounts_with_spend' => 0
            ];
        }

        // 聚合查询insights数据
        $insightsData = FbAdAccountInsight::whereIn('account_id', $accountSourceIds)
            ->whereBetween('date_start', [$date_start, $date_stop])
            ->where('spend', '>', 0)
            ->selectRaw('
                SUM(spend) as total_spend,
                SUM(inline_link_clicks) as total_link_clicks,
                COUNT(DISTINCT account_id) as accounts_with_spend
            ')
            ->first();

        $totalSpend = $insightsData->total_spend ?? 0;
        $totalLinkClicks = $insightsData->total_link_clicks ?? 0;
        $accountsWithSpendCount = $insightsData->accounts_with_spend ?? 0;

        $totalOfferClicks = 0;
        $totalOfferLeads = 0;
        $totalOfferConversions = 0;
        $totalRevenue = 0;

        // 🎯 按时区分组计算offer数据
        if ($totalSpend > 0) {
            $accountsWithSpend = FbAdAccount::whereIn('source_id', $accountSourceIds)
                ->whereHas('insights', function($query) use ($date_start, $date_stop) {
                    $query->whereBetween('date_start', [$date_start, $date_stop])
                          ->where('spend', '>', 0);
                })
                ->get(['source_id', 'timezone_name']);

            // 按时区分组账户
            $accountsByTimezone = $accountsWithSpend->groupBy('timezone_name');

            foreach ($accountsByTimezone as $timezone => $accounts) {
                $accountIds = $accounts->pluck('source_id')->toArray();

                // 计算该时区的时间范围
                $timezoneToUse = $timezone ?? 'UTC';
                $startDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_start, $timezoneToUse)->startOfDay()->setTimezone('UTC');
                $endDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date_stop, $timezoneToUse)->endOfDay()->setTimezone('UTC');

                // 获取这些账户对应的Campaign source_ids
                $campaignSourceIds = DB::table('fb_campaigns')
                    ->join('fb_ad_accounts', 'fb_campaigns.fb_ad_account_id', '=', 'fb_ad_accounts.id')
                    ->whereIn('fb_ad_accounts.source_id', $accountIds)
                    ->pluck('fb_campaigns.source_id')
                    ->toArray();

                if (!empty($campaignSourceIds)) {
                    // 查询该时区下的offer clicks
                    $timezoneOfferClicks = Click::whereIn('fb_campaign_source_id', $campaignSourceIds)
                        ->whereBetween('click_datetime', [$startDateInTimeZone, $endDateInTimeZone])
                        ->count();

                    $totalOfferClicks += $timezoneOfferClicks;

                    // 查询该时区下的offer conversions
                    $timezoneConversionData = Conversion::whereIn('fb_campaign_source_id', $campaignSourceIds)
                        ->whereBetween('conversion_datetime', [$startDateInTimeZone, $endDateInTimeZone])
                        ->selectRaw('
                            SUM(CASE WHEN price = 0 THEN 1 ELSE 0 END) as total_leads,
                            SUM(CASE WHEN price > 0 THEN 1 ELSE 0 END) as total_conversions,
                            SUM(CASE WHEN price > 0 THEN price ELSE 0 END) as total_revenue
                        ')
                        ->first();

                    $totalOfferLeads += $timezoneConversionData->total_leads ?? 0;
                    $totalOfferConversions += $timezoneConversionData->total_conversions ?? 0;
                    $totalRevenue += $timezoneConversionData->total_revenue ?? 0;
                }
            }
        }

        // 计算利润和ROI
        $totalProfit = round($totalRevenue - $totalSpend, 2);
        $roi = ($totalSpend != 0) ? round($totalProfit / $totalSpend, 4) : 0;

        return [
            'spend' => round($totalSpend, 2),
            'link_clicks' => (int)$totalLinkClicks,
            'offer_clicks' => $totalOfferClicks,
            'leads' => (int)$totalOfferLeads,
            'sales' => (int)$totalOfferConversions,
            'revenue' => round($totalRevenue, 2),
            'profit' => $totalProfit,
            'roi' => $roi,
            'ad_accounts_count' => count($accountSourceIds),
            'accounts_with_spend' => $accountsWithSpendCount
        ];
    }
}
