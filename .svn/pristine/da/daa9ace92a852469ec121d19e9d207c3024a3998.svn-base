<?php

namespace App\Http\Controllers;

use App\Http\Resources\FbCampaignResource;
use App\Http\Services\MetricsService;
use App\Jobs\ActionCopyFbObject;
use App\Jobs\ActionCopyFbObjectSync;
use App\Jobs\ActionRenameFbObject;
use App\Jobs\ActionUpdateFbAdItemBudget;
use App\Jobs\BatchUpdateFbItemStatus;
use App\Jobs\FacebookDeleteAdObject;
use App\Jobs\FacebookFetchAd;
use App\Jobs\FacebookFetchAdAccountInsights;
use App\Jobs\FacebookFetchAdInsights;
use App\Jobs\FacebookFetchAdset;
use App\Jobs\FacebookFetchAdsetInsights;
use App\Jobs\FacebookFetchAdsetV2;
use App\Jobs\FacebookFetchAdV2;
use App\Jobs\FacebookFetchCampaign;
use App\Jobs\FacebookFetchCampaignInsights;
use App\Jobs\FacebookFetchCampaignV2;
use App\Jobs\FacebookSyncAdAccount;
use App\Jobs\FacebookUpdateBidAmount;
use App\Jobs\FacebookUpdateBidStrategy;
use App\Jobs\FacebookCboToAbo;
use App\Jobs\TriggerFacebookFetchCampaign;
use App\Jobs\TriggerFacebookFetchInsights;
use App\Models\FbAd;
use App\Models\FbAdAccount;
use App\Models\FbAdset;
use App\Models\FbCampaign;
use App\Models\User;
use App\Utils\DevUtils;
use App\Utils\FbUtils;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class FbCampaignController extends BaseController
{
    protected $metricsService;

    public function __construct(MetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortOrder', 'asc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $tagNames = $request->has('tags') ? explode(',', $request->get('tags')) : [];
        Log::debug($tagNames);

        $searchableFields = [
            'name' => $request->get('name'),
            'endpoint' => $request->get('endpoint'),
            'date_start' => $request->get('date_start'),
            'date_stop' => $request->get('date_end')
        ];

        $campaigns = FbCampaign::searchByTagNames($tagNames)->search($searchableFields)->orderBy($sortField, $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => FbCampaignResource::collection($campaigns->items()),
            'pageSize' => $campaigns->perPage(),
            'pageNo' => $campaigns->currentPage(),
            'totalPage' => $campaigns->lastPage(),
            'totalCount' => $campaigns->total(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(FbCampaign $fbCampaign)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FbCampaign $fbCampaign)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FbCampaign $fbCampaign)
    {
        //
    }

    /**
     * 获取广告状态，再获取广告数据,指定时间，所有账户都用相同的时间范围
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchFbCampaignData(Request $request)
    {
        $validatedData = $request->validate([
            'fb_ad_account_ids' => 'array',
            'fb_ad_account_ids.*' => 'exists:fb_ad_accounts,id',
            'date_start' => 'nullable',
            'date_stop' => 'nullable'
        ]);

        $user = $request->user();

        foreach ($validatedData['fb_ad_account_ids'] as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

        $date_start = $request->get('date_start', null);
        $date_stop = $request->get('date_stop', null);


        TriggerFacebookFetchCampaign::dispatch($validatedData['fb_ad_account_ids'], $date_start, $date_stop, true)->onQueue('facebook');
        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ]);
    }

    public function fetchFbAdAccountInfo(Request $request)
    {
        Log::info("fetchFbAdAccountInfo");
        $validatedData = $request->validate([
            'fb_ad_account_ids' => 'required|array',
            'fb_ad_account_ids.*' => 'string|exists:fb_ad_accounts,id',
        ]);

        $user = $request->user();

        foreach ($validatedData['fb_ad_account_ids'] as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

        $fbAdAccounts = FbAdAccount::query()->whereIn('id', $validatedData['fb_ad_account_ids'])->get();
        Log::info($fbAdAccounts->count());
        foreach ($fbAdAccounts as $fbAdAccount) {
            // 只同步广告账户的数据
            FacebookSyncAdAccount::dispatch($fbAdAccount->id, null, null, null, false)->onQueue('facebook');
        }

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ]);
    }

    /**
     * 获取广告状态，再戈获取数据，但是，根据每个账户的时间，确定日期范围
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchFbCampaignDataRecently(Request $request)
    {
        $validatedData = $request->validate([
            'fb_ad_account_ids' => 'array',
            'fb_ad_account_ids.*' => 'string|exists:fb_ad_accounts,id',
            'days' => 'required|numeric',
            'all' => 'boolean',
            'pull_insights' => 'boolean'
        ]);
        Log::debug("fetchFbCampaignDataRecently, days: {$validatedData['days']}");

        $user = $request->user();

        foreach ($validatedData['fb_ad_account_ids'] as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

        $all = $request->get('all', false);
        $next = $request->get('pull_insights', true);

        $fbAdAccounts = FbAdAccount::query()->whereIn('id', $validatedData['fb_ad_account_ids'])->get();
        foreach ($fbAdAccounts as $fbAdAccount) {
            $timezone_name = $fbAdAccount['timezone_name'];
            Log::debug("==> trigger fetch insight: fb ad account: {$fbAdAccount->id}, timezone: {$timezone_name}");
            $days = FbUtils::getLastNDays($validatedData['days'], $timezone_name);

            // 只获取 Campaign 状态数据
            FacebookFetchCampaign::dispatch($fbAdAccount->id, null, null, null, false, $all, $validatedData['days'])->onQueue('facebook');
            // 获取广告Insight 数据, 按天来，每个job延迟5s
            if ($next) {
                foreach ($days as $index => $day) {
                    FacebookFetchAdAccountInsights::dispatch($fbAdAccount->id, $day, $day, null, true)
                        ->onQueue('facebook')->delay(5 * $index);
                }
            }
        }

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language ),
            'success' => true
        ]);
    }

    public function fetchFbInsights(Request $request)
    {
        $validatedData = $request->validate([
            'fb_ad_account_ids' => 'array',
            'fb_ad_account_ids.*' => 'string'
        ]);

        TriggerFacebookFetchInsights::dispatch($validatedData['fb_ad_account_ids'])->onQueue('facebook');
        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ]);
    }

    public function account_insights(Request $request)
    {
        $request->validate([
            'date_start' => 'nullable',
            'date_stop' => 'nullable|gte:date_start',
            'ad_account_ids' => 'array',
            'ad_account_ids.*' => 'string|exists:fb_ad_accounts,id',
            'others' => 'array|nullable',
            'campaign_names' => 'array|nullable',
            'campaign_tags' => 'array|nullable',
            'export' => 'nullable|boolean'
        ]);

        $date_start = $request->input('date_start');
        $date_stop = $request->input('date_stop');
        $ad_account_ids = $request->input('ad_account_ids', []);
        $others = $request->input('others', []);
        $user = $request->user();

        foreach ($ad_account_ids as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

        Log::debug('others');
        Log::debug($others);
        $exclude_archived_campaigns = collect($others)->contains('exclude_archived_campaign');
        $campaign_names = $request->input('campaign_names', []);
        $campaign_tags = $request->input('campaign_tags', []);
        Log::debug($campaign_tags);

        if(empty($date_start) || empty($date_stop) || empty($ad_account_ids)) {
            return response()->json([
                'data' => [],
                'totalCount' => 0,
            ]);
        }

//        $metrics_list = [];
//        foreach ($ad_account_ids as $ad_account_id) {
//            $ad_account = FbAdAccount::query()->firstWhere('id', $ad_account_id);
//            if ($ad_account) {
//                $timezone = 'UTC'; // 获取时区
//                $metrics = $ad_account->get_metrics($date_start, $date_stop, $timezone);
//                array_push($metrics_list, $metrics);
//            }
//        }
        $metrics_list = $this->metricsService->get_metrics_by_ad_account($ad_account_ids, $date_start, $date_stop,
            $campaign_names, $campaign_tags, $exclude_archived_campaigns, $request->user());

        if (!$request->get('export')) {
            return response()->json([
                'data' => $metrics_list,
                'totalCount' => count($metrics_list)
            ]);
        } else {
            $timestamp = date('Y-m-d-H-i'); // 格式化为 '2024-10-09-23-05'
            $filename = "export-ad-account-{$timestamp}.csv"; // 文件名添加时间戳

            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ];

            return response()->stream(function () use ($metrics_list) {
                $handle = fopen('php://output', 'w');

                fputcsv($handle, ['ad_account_id', 'ad_account_name', 'currency', 'account_status','spend',
                    'offer_conversions_value', 'roi', 'purchase_roas', 'offer_clicks', 'offer_leads', 'offer_epc',
                    'offer_cpc', 'offer_cpl', 'offer_epl', 'link_clicks', 'link_ctr', 'taken_rate', 'cpm', 'created_time'
                    ]); // CSV Header

                foreach ($metrics_list as $metric) {
                    fputcsv($handle, [
                        $metric['ad_account_id'],
                        $metric['ad_account_name'],
                        $metric['currency'],
                        $metric['account_status'],
                        $metric['spend'],
                        $metric['offer_conversions_value'],
                        $metric['roi'],
                        $metric['purchase_roas'],
                        $metric['offer_clicks'],
                        $metric['offer_leads'],
                        $metric['offer_epc'],
                        $metric['offer_cpc'],
                        $metric['offer_cpl'],
                        $metric['offer_epl'],
                        $metric['link_clicks'],
                        $metric['link_ctr'],
                        $metric['taken_rate'],
                        $metric['cpm'],
                        $metric['created_time'] ?? ""
                    ]);
                }
                fclose($handle);
            }, 200, $headers);
        }
    }

    public function campaign_insights(Request $request)
    {
        $request->validate([
            'date_start' => 'nullable',
            'date_stop' => 'nullable|gte:date_start',
            'campaign_ids' => 'array',
            'campaign_ids.*' => 'string|exists:fb_campaigns,id',
            'export' => 'nullable|boolean'
        ]);

        $date_start = $request->input('date_start');
        $date_stop = $request->input('date_stop');
        $campaign_ids = $request->input('campaign_ids');
        $user = $request->user();

        $adAccountIds = FbAdAccount::whereHas('fbCampaigns', function($query) use ($campaign_ids) {
            $query->whereIn('id', $campaign_ids);
        })->pluck('id');

        if (count($adAccountIds) === 0) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        foreach ($adAccountIds as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

        if(empty($date_start) || empty($date_stop) || empty($campaign_ids)) {
            return response()->json([
                'data' => [],
                'totalCount' => 0,
            ]);
        }

        // TODO: campaign id 权限控制
        $metrics_list = $this->metricsService->get_metrics_by_campaign($campaign_ids, $date_start, $date_stop, $request->user());

        if (!$request->get('export')) {
            return response()->json([
                'data' => $metrics_list,
                'pageSize' => count($metrics_list),
                'pageNo' => 1,
                'totalPage' => 1,
                'totalCount' => count($metrics_list)
            ]);
        } else {
            $timestamp = date('Y-m-d-H-i'); // 格式化为 '2024-10-09-23-05'
            $filename = "export-campaign-{$timestamp}.csv"; // 文件名添加时间戳

            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ];

            return response()->stream(function () use ($metrics_list) {
                $handle = fopen('php://output', 'w');

                fputcsv($handle, ['ad_account_id', 'ad_account_name', 'currency', 'account_status', 'campaign_id',
                    'campaign_name', 'effective_status', 'daily_budget', 'spend', 'offer_conversions_value', 'roi', 'purchase_roas', 'offer_clicks', 'offer_leads', 'offer_epc',
                    'offer_cpc', 'offer_cpl', 'offer_epl', 'link_clicks', 'link_ctr', 'taken_rate', 'cpm', 'created_time'
                ]); // CSV Header

                foreach ($metrics_list as $metric) {
                    fputcsv($handle, [
                        $metric['ad_account_id'],
                        $metric['ad_account_name'],
                        $metric['currency'],
                        $metric['account_status'],
                        $metric['campaign_id'],
                        $metric['campaign_name'],
                        $metric['effective_status'],
                        $metric['daily_budget'],
                        $metric['spend'],
                        $metric['offer_conversions_value'],
                        $metric['roi'],
                        $metric['purchase_roas'],
                        $metric['offer_clicks'],
                        $metric['offer_leads'],
                        $metric['offer_epc'],
                        $metric['offer_cpc'],
                        $metric['offer_cpl'],
                        $metric['offer_epl'],
                        $metric['link_clicks'],
                        $metric['link_ctr'],
                        $metric['taken_rate'],
                        $metric['cpm'],
                        $metric['created_time'],
                    ]);
                }
                fclose($handle);
            }, 200, $headers);
        }
    }

    public function adsets_insights(Request $request)
    {
        $request->validate([
            'date_start' => 'nullable',
            'date_stop' => 'nullable|gte:date_start',
            'adset_ids' => 'nullable|array',
            'adset_ids.*' => 'string|exists:fb_adsets,id',
            'export' => 'nullable|boolean'
        ]);

        $date_start = $request->input('date_start');
        $date_stop = $request->input('date_stop');
        $adset_ids = $request->input('adset_ids');
        $user = $request->user();

        $adAccountIds = FbAdAccount::whereHas('fbAdsets', function($query) use ($adset_ids) {
            $query->whereIn('id', $adset_ids);
        })->pluck('id');

        if (count($adAccountIds) === 0) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        foreach ($adAccountIds as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

        if(empty($date_start) || empty($date_stop) || empty($adset_ids)) {
            return response()->json([
                'data' => [],
                'totalCount' => 0,
            ]);
        }

        $metrics_list = $this->metricsService->get_metrics_by_adset($adset_ids, $date_start, $date_stop, $request->user());

        if (!$request->get('export')) {
            return response()->json([
                'data' => $metrics_list,
                'pageSize' => count($metrics_list),
                'pageNo' => 1,
                'totalPage' => 1,
                'totalCount' => count($metrics_list)
            ]);
        } else {
            $timestamp = date('Y-m-d-H-i'); // 格式化为 '2024-10-09-23-05'
            $filename = "export-adset-{$timestamp}.csv"; // 文件名添加时间戳

            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ];

            return response()->stream(function () use ($metrics_list) {
                $handle = fopen('php://output', 'w');

                fputcsv($handle, ['ad_account_id', 'ad_account_name', 'currency', 'account_status', 'campaign_id',
                    'campaign_name', 'adset_id', 'adset_name', 'effective_status', 'daily_budget', 'spend', 'offer_conversions_value', 'roi', 'purchase_roas', 'offer_clicks', 'offer_leads', 'offer_epc',
                    'offer_cpc', 'offer_cpl', 'offer_epl', 'link_clicks', 'link_ctr', 'taken_rate', 'cpm', 'created_time',
                ]); // CSV Header

                foreach ($metrics_list as $metric) {
                    fputcsv($handle, [
                        $metric['ad_account_id'],
                        $metric['ad_account_name'],
                        $metric['currency'],
                        $metric['account_status'],
                        $metric['campaign_id'],
                        $metric['campaign_name'],
                        $metric['adset_id'],
                        $metric['adset_name'],
                        $metric['effective_status'],
                        $metric['daily_budget'],
                        $metric['spend'],
                        $metric['offer_conversions_value'],
                        $metric['roi'],
                        $metric['purchase_roas'],
                        $metric['offer_clicks'],
                        $metric['offer_leads'],
                        $metric['offer_epc'],
                        $metric['offer_cpc'],
                        $metric['offer_cpl'],
                        $metric['offer_epl'],
                        $metric['link_clicks'],
                        $metric['link_ctr'],
                        $metric['taken_rate'],
                        $metric['cpm'],
                        $metric['created_time'],
                    ]);
                }
                fclose($handle);
            }, 200, $headers);
        }
    }

    public function ad_insights(Request $request)
    {
        $request->validate([
            'date_start' => 'nullable',
            'date_stop' => 'nullable|gte:date_start',
            'ad_ids' => 'nullable|array',
            'ad_ids.*' => 'string|exists:fb_ads,id',
            'export' => 'nullable|boolean'
        ]);

        $date_start = $request->input('date_start');
        $date_stop = $request->input('date_stop');
        $ad_ids = $request->input('ad_ids');
        $user = $request->user();

        $adAccountIds = FbAdAccount::whereHas('fbAds', function($query) use ($ad_ids) {
            $query->whereIn('fb_ads.id', $ad_ids);
        })->pluck('id');

        if (count($adAccountIds) === 0) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        foreach ($adAccountIds as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

        if(empty($date_start) || empty($date_stop) || empty($ad_ids)) {
            return response()->json([
                'data' => [],
                'totalCount' => 0,
            ]);
        }

        $metrics_list = $this->metricsService->get_metrics_by_ad($ad_ids, $date_start, $date_stop, $request->user());

        if (!$request->get('export')) {
            return response()->json([
                'data' => $metrics_list,
                'pageSize' => count($metrics_list),
                'pageNo' => 1,
                'totalPage' => 1,
                'totalCount' => count($metrics_list)
            ]);
        } else {
            $timestamp = date('Y-m-d-H-i'); // 格式化为 '2024-10-09-23-05'
            $filename = "export-adset-{$timestamp}.csv"; // 文件名添加时间戳

            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ];

            return response()->stream(function () use ($metrics_list) {
                $handle = fopen('php://output', 'w');

                fputcsv($handle, ['ad_account_id', 'ad_account_name', 'currency', 'account_status', 'campaign_id',
                    'campaign_name', 'adset_id', 'adset_name', 'ad_id', 'ad_name', 'effective_status', 'daily_budget', 'spend',
                    'offer_conversions_value', 'roi', 'purchase_roas', 'offer_clicks', 'offer_leads', 'offer_epc',
                    'offer_cpc', 'offer_cpl', 'offer_epl', 'link_clicks', 'link_ctr', 'taken_rate', 'cpm', 'created_time',
                ]); // CSV Header

                foreach ($metrics_list as $metric) {
                    fputcsv($handle, [
                        $metric['ad_account_id'],
                        $metric['ad_account_name'],
                        $metric['currency'],
                        $metric['account_status'],
                        $metric['campaign_id'],
                        $metric['campaign_name'],
                        $metric['adset_id'],
                        $metric['adset_name'],
                        $metric['ad_id'],
                        $metric['ad_name'],
                        $metric['effective_status'],
                        $metric['daily_budget'],
                        $metric['spend'],
                        $metric['offer_conversions_value'],
                        $metric['roi'],
                        $metric['purchase_roas'],
                        $metric['offer_clicks'],
                        $metric['offer_leads'],
                        $metric['offer_epc'],
                        $metric['offer_cpc'],
                        $metric['offer_cpl'],
                        $metric['offer_epl'],
                        $metric['link_clicks'],
                        $metric['link_ctr'],
                        $metric['taken_rate'],
                        $metric['cpm'],
                        $metric['created_time'],
                    ]);
                }
                fclose($handle);
            }, 200, $headers);
        }
    }

    public function archive(Request $request)
    {
        #TODO: 权限控制
        $validatedData = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|exists:fb_campaigns,id'
        ]);
        $ids = $request->get('ids'); // 从请求中获取要归档的账户ID
        $user = $request->user();

        $adAccounts = FbAdAccount::whereHas('fbCampaigns', function($query) use ($ids) {
            $query->whereIn('id', $ids);
        })->get();

        foreach ($adAccounts as $adAccount) {
            if (!$user->can('operate', $adAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

        FbCampaign::whereIn('id', $ids)->update(['is_archived' => true]); // 更新数据库
        return response()->json(['message' => 'Campaign archived successfully.']);
    }

    public function unarchive(Request $request)
    {
        $validatedData = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string'
        ]);
        $ids = $request->get('ids'); // 从请求中获取要归档的账户ID
        $user = $request->user();

        $adAccounts = FbAdAccount::whereHas('fbCampaigns', function($query) use ($ids) {
            $query->whereIn('id', $ids);
        })->get();

        foreach ($adAccounts as $adAccount) {
            if (!$user->can('operate', $adAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

        FbCampaign::whereIn('id', $ids)->update(['is_archived' => false]); // 更新数据库
        return response()->json(['message' => 'Campaign unarchived successfully.']);
    }

    public function batch_update_object_status(Request $request)
    {
        # TODO: 权限管理，只在用户有个号的权限，个号有广告号的权限，广告号有这个 Campaign的时候才能操作

        $validatedData = $request->validate([
            'ids' => 'array',
            'ids.*' => 'string',
            'object_type' => 'string|in:campaign,adset,ad',
            'status' => 'string|in:ACTIVE,PAUSED',
        ]);
//        new BatchUpdateFbItemStatus($object_value, 'ACTIVE', 'campaign');
        $ids = $validatedData['ids'];
        $user = $request->user();
        $object_type = $validatedData['object_type'];

        if ($object_type === 'campaign') {
            $adAccounts = FbAdAccount::whereHas('fbCampaigns', function($query) use ($ids) {
                $query->whereIn('source_id', $ids);
            })->get();

        } else if ($object_type === 'adset') {
            $adAccounts = FbAdAccount::whereHas('fbAdsets', function($query) use ($ids) {
                $query->whereIn('.source_id', $ids);
            })->get();
        } else if ($object_type === 'ad') {
            $adAccounts = FbAdAccount::whereHas('fbAds', function($query) use ($ids) {
                $query->whereIn('fb_ads.source_id', $ids);
            })->get();
        }

        if (count($adAccounts) === 0) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        foreach ($adAccounts as $adAccount) {
            if (!$user->can('operate', $adAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

        BatchUpdateFbItemStatus::dispatch($validatedData['ids'], $validatedData['status'],
            $validatedData['object_type'])->onQueue('frontend');

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ]);
    }

    public function update_object_budget(Request $request)
    {
        #TODO: 权限控制
        $validatedData = $request->validate([
            'id' => 'required|string',
            'object_type' => 'string|in:campaign,adset',
            'budget_type' => 'string|in:daily_budget,lifetime_budget',
            'budget' => 'required|string'
        ]);

        $user = $request->user();
        $object_type = $validatedData['object_type'];
        $id = $validatedData['id'];

        if ($object_type === 'campaign') {
            $adAccounts = FbAdAccount::whereHas('fbCampaigns', function($query) use ($id) {
                $query->where('source_id', $id);
            })->get();

        } else if ($object_type === 'adset') {
            $adAccounts = FbAdAccount::whereHas('fbAdsets', function($query) use ($id) {
                $query->where('source_id', $id);
            })->get();
        }

        if (count($adAccounts) === 0) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        foreach ($adAccounts as $adAccount) {
            if (!$user->can('operate', $adAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

        ActionUpdateFbAdItemBudget::dispatch($validatedData['id'], $validatedData['object_type'],
            $validatedData['budget_type'], $validatedData['budget'])->onQueue('frontend');

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ]);
    }

    /**
     * 批量更新预算
     */
    public function batch_update_object_budget(Request $request)
    {
        $validatedData = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|string',
            'items.*.object_type' => 'required|string|in:campaign,adset',
            'items.*.budget_type' => 'required|string|in:daily_budget,lifetime_budget',
            'items.*.budget' => 'required|string'
        ]);

        $user = $request->user();
        $items = $validatedData['items'];

        // 收集所有需要检查权限的广告账户
        $campaignIds = [];
        $adsetIds = [];

        foreach ($items as $item) {
            if ($item['object_type'] === 'campaign') {
                $campaignIds[] = $item['id'];
            } else if ($item['object_type'] === 'adset') {
                $adsetIds[] = $item['id'];
            }
        }

        // 检查权限
        $allAdAccounts = collect();

        if (!empty($campaignIds)) {
            $campaignAdAccounts = FbAdAccount::whereHas('fbCampaigns', function($query) use ($campaignIds) {
                $query->whereIn('source_id', $campaignIds);
            })->get();
            $allAdAccounts = $allAdAccounts->merge($campaignAdAccounts);
        }

        if (!empty($adsetIds)) {
            $adsetAdAccounts = FbAdAccount::whereHas('fbAdsets', function($query) use ($adsetIds) {
                $query->whereIn('source_id', $adsetIds);
            })->get();
            $allAdAccounts = $allAdAccounts->merge($adsetAdAccounts);
        }

        $allAdAccounts = $allAdAccounts->unique('id');

        if ($allAdAccounts->isEmpty()) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        // 检查用户对所有广告账户的权限
        foreach ($allAdAccounts as $adAccount) {
            if (!$user->can('operate', $adAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

                // 按广告账户分组任务，避免API频率限制
        $tasksByAdAccount = [];
        foreach ($items as $item) {
            // 根据对象类型和ID找到对应的广告账户
            $adAccount = null;
            if ($item['object_type'] === 'campaign') {
                $campaign = FbCampaign::where('source_id', $item['id'])->first();
                if ($campaign) {
                    $adAccount = $campaign->fbAdAccount;
                }
            } else if ($item['object_type'] === 'adset') {
                $adset = FbAdset::where('source_id', $item['id'])->first();
                if ($adset) {
                    $adAccount = $adset->fbAdAccount;
                }
            }

            if ($adAccount) {
                $adAccountId = $adAccount->id;
                if (!isset($tasksByAdAccount[$adAccountId])) {
                    $tasksByAdAccount[$adAccountId] = [];
                }
                $tasksByAdAccount[$adAccountId][] = $item;
            }
        }

        // 提交按广告账户分组的任务
        foreach ($tasksByAdAccount as $accountTasks) {
            $delaySeconds = 0;
            foreach ($accountTasks as $task) {
                ActionUpdateFbAdItemBudget::dispatch(
                    $task['id'],
                    $task['object_type'],
                    $task['budget_type'],
                    $task['budget']
                )->onQueue('frontend')->delay($delaySeconds);

                $delaySeconds += 3; // 同一个广告账户的任务间隔3秒
            }
        }

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true,
            'details' => [
                'items_count' => count($items),
                'ad_accounts_count' => count($tasksByAdAccount),
                'tasks_by_ad_account' => array_map(function($tasks) {
                    return [
                        'tasks_count' => count($tasks),
                        'max_delay_seconds' => (count($tasks) - 1) * 3
                    ];
                }, $tasksByAdAccount)
            ]
        ]);
    }

    public function copy_object(Request $request)
    {
        #TODO: 权限控制
        $validatedData = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string',
            'object_type' => 'required|string|in:campaign,adset',
            'count' => 'required|numeric|gte:0'
        ]);

        $user = $request->user();
        $object_type = $validatedData['object_type'];
        $ids = $validatedData['ids'];

        if ($object_type === 'campaign') {
            $adAccounts = FbAdAccount::whereHas('fbCampaigns', function($query) use ($ids) {
                $query->whereIn('source_id', $ids);
            })->get();

        } else if ($object_type === 'adset') {
            $adAccounts = FbAdAccount::whereHas('fbAdsets', function($query) use ($ids) {
                $query->whereIn('source_id', $ids);
            })->get();
        }

        if (count($adAccounts) === 0) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        foreach ($adAccounts as $adAccount) {
            if (!$user->can('operate', $adAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

//        foreach ($validatedData['ids'] as $source_id) {
//            ActionCopyFbObject::dispatch($source_id, $validatedData['object_type'], $validatedData['count'])->onQueue('facebook');
//        }

        foreach ($validatedData['ids'] as $source_id) {
            $count = $validatedData['count'];
            for ($i=0; $i<$count; $i++) {
                ActionCopyFbObjectSync::dispatch($source_id, $validatedData['object_type'])
                    ->onQueue('frontend')->delay($i*10);
            }
        }
        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ]);
    }

    public function rename_object(Request $request)
    {
        #TODO: 权限控制
        $validatedData = $request->validate([
            'id' => 'required|string',
            'object_type' => 'required|string|in:campaign,adset,ad',
            'object_name' => 'required|string'
        ]);

        $user = $request->user();
        $object_type = $validatedData['object_type'];
        $object_source_id = $validatedData['id'];

        if ($object_type === 'campaign') {
            $fbObject = FbCampaign::query()->firstWhere('source_id', $object_source_id);
            if ($fbObject && $fbObject->fbAdAccount) {
                Log::debug($fbObject->fbAdAccount->source_id);

                $fbAdAccount = $fbObject->fbAdAccount;

                if (!$user->can('operate', $fbAdAccount)) {
                    Log::debug("can not operate");
                    return response()->json(['message' => 'Unauthorized action.'], 403);
                }
            } else {
                return response()->json(['message' => 'Resource Not Found'], 404);
            }

        } else if ($object_type === 'adset') {
            $fbObject = FbAdset::query()->firstWhere('source_id', $object_source_id);
            if ($fbObject && $fbObject->fbAdAccount) {
                Log::debug($fbObject->fbAdAccount);
                if (!$user->can('operate', $fbObject->fbAdAccount)) {
                    return response()->json(['message' => 'Unauthorized action.'], 403);
                }
            } else {
                return response()->json(['message' => 'Resource Not Found'], 404);
            }
        } else if ($object_type === 'ad') {
            $fbObject = FbAd::query()->firstWhere('source_id', $object_source_id);
            if ($fbObject && $fbObject->fbAdAccount) {
                if (!$user->can('operate', $fbObject->fbAdAccount)) {
                    return response()->json(['message' => 'Unauthorized action.'], 403);
                }
            } else {
                return response()->json(['message' => 'Resource Not Found'], 404);
            }
        }

        ActionRenameFbObject::dispatch($validatedData['id'], $validatedData['object_type'],
            $validatedData['object_name'])->onQueue('frontend');

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ]);
    }

    /**
     * 只同步广告账户层同的 insights
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync_ad_account_insights(Request $request)
    {
        $validatedData = $request->validate([
            'fb_ad_account_ids' => 'array',
            'fb_ad_account_ids.*' => 'string|exists:fb_ad_accounts,id',
            'date_start' => 'required|date_format:Y-m-d|before_or_equal:date_stop',
            'date_stop' => 'required|date_format:Y-m-d|after_or_equal:date_start',
            'mode' => 'required|numeric|in:1,2,3,4'
        ]);
        $mode = $validatedData['mode'];
        Log::debug("sync mode: {$mode}");

        $date_start = Carbon::parse($validatedData['date_start']);
        $date_stop = Carbon::parse($validatedData['date_stop']);
        $days_diff = $date_stop->diffInDays($date_start);

        $user = $request->user();

        foreach ($validatedData['fb_ad_account_ids'] as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

        $fbAdAccounts = FbAdAccount::query()->whereIn('id', $validatedData['fb_ad_account_ids'])->get();

        $syncStatusjobs = [];
        $insightJobs = [];
        $fetchCampaignJobs = [];
        foreach ($fbAdAccounts as $fbAdAccount) {
            if ($mode === 1 || $mode === 4) {
                $insightJobs[] = new FacebookFetchAdAccountInsights($fbAdAccount->id, $validatedData['date_start'],
                    $validatedData['date_stop'], null, false);
            }

            if ($mode === 2 || $mode === 4) {
                $syncStatusjobs[] = new FacebookSyncAdAccount($fbAdAccount->id, $validatedData['date_start'],
                    $validatedData['date_stop'], null, false);
            }

            if ($mode === 3 || $mode === 4) {
                if ($mode === 3) {
                    $fetchCampaignJobs[] = new FacebookFetchCampaignV2($fbAdAccount->id, null, null,
                        null, true, false, false);
                } else {
                    $fetchCampaignJobs[] = new FacebookFetchCampaignV2($fbAdAccount->id, null, $validatedData['date_start'],
                        $validatedData['date_stop'], true, true, true);
                }
//                $fetchCampaignJobs[] = new FacebookFetchCampaign($fbAdAccount->id, $validatedData['date_start'],
//                    $validatedData['date_stop'], null, false, true, $days_diff);

            }

        }

        if ( $mode===1 || $mode === 4) {
            Bus::batch($insightJobs)->finally(function ($batch) {
                Log::debug("finish fetch all ad account insight jobs");
            })->onQueue('frontend')->allowFailures()->dispatch();
        }

        if ($mode===2 || $mode === 4) {
            Bus::batch($syncStatusjobs)->finally(function ($batch) {
                Log::debug("finished sync ad account status jobs");
            })->onQueue('frontend')->allowFailures()->dispatch();
        }

        if ($mode===3 || $mode === 4) {
            Bus::batch($fetchCampaignJobs)->finally(function ($batch) {
                Log::debug("finish fetch all ad account campaigns jobs");
            })->onQueue('frontend')->allowFailures()->dispatch();
        }


        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language ),
            'success' => true
        ]);

    }

    /**
     * 只同步Campaign层面的 insights
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync_campaign_insights(Request $request)
    {
        Log::debug("sync campaigns");
        $validatedData = $request->validate([
            'campaign_ids' => 'array',
            'campaign_ids.*' => 'string|exists:fb_campaigns,id',
            'date_start' => 'required|date_format:Y-m-d|before_or_equal:date_stop',
            'date_stop' => 'required|date_format:Y-m-d|after_or_equal:date_start',
            'mode' => 'required|numeric|in:1,2,3,4'
        ]);
        $mode = $validatedData['mode'];
        Log::debug("sync mode: {$mode}");

        $date_start = Carbon::parse($validatedData['date_start']);
        $date_stop = Carbon::parse($validatedData['date_stop']);
        $days_diff = $date_stop->diffInDays($date_start);

        $user = $request->user();

        $campaignIds = $validatedData['campaign_ids'];
        Log::debug("ids:", $campaignIds);
        $campaignWithAdAccount = FbCampaign::query()->whereIn('id', $campaignIds)->with('fbAdAccount')
            ->get();
        $fbAdAccountIds = $campaignWithAdAccount->pluck('fbAdAccount.id')->unique()->toArray();
        Log::debug($fbAdAccountIds);

        foreach ($fbAdAccountIds as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

        $campaignsGrouped = $campaignWithAdAccount->groupBy(function ($campaign) {
            return $campaign->fbAdAccount->id; // 按 Ad Account ID 分组
        });
        // 处理结果，获取 AdAccount ID 和对应的 Campaign IDs
        $result = [];
        foreach ($campaignsGrouped as $adAccountId => $campaigns) {
            $result[$adAccountId] = $campaigns->pluck('source_id')->toArray(); // 获取该 Ad Account 下的 Campaign IDs
        }

        $syncStatusjobs = [];
        $insightJobs = [];
        $fetchAdsetJobs = [];

        foreach ($result as $fbAdAccountId => $campaignSourceIds) {
            if ( $mode===1 || $mode === 4) {
                $insightJobs[] = new FacebookFetchCampaignInsights($fbAdAccountId, $validatedData['date_start'],
                    $validatedData['date_stop'], null, false, [
                        'field' => 'campaign.id',
                        'operator' => 'IN',
                        'value' => $campaignSourceIds
                    ]);
            }

            if ( $mode===2 || $mode === 4) {
                //
//                $date_start = $mode === 2 ? null : $validatedData['date_start'];
//                $date_stop = $mode === 2 ? null : $validatedData['date_stop'];
//                $syncStatusjobs[] = new FacebookFetchCampaign($fbAdAccountId, $date_start,
//                    $date_stop, null, false, false, 1, [
//                        'field' => 'id',
//                        'operator' => 'IN',
//                        'value' => $campaignSourceIds
//                    ]);
                $syncStatusjobs[] = new FacebookFetchCampaignV2($fbAdAccountId, null, null,
                    null, false, false, false, [
                        'field' => 'id',
                        'operator' => 'IN',
                        'value' => $campaignSourceIds
                    ]);
            }

            if ( $mode===3 || $mode === 4) {
//                $fetchAdsetJobs[]= new FacebookFetchAdset($fbAdAccountId, $validatedData['date_start'],
//                    $validatedData['date_stop'], null, false, true, $days_diff, [
//                        'field' => 'campaign.id',
//                        'operator' => 'IN',
//                        'value' => $campaignSourceIds
//                    ]);

                if ($mode === 3) {
                    $fetchAdsetJobs[]= new FacebookFetchAdsetV2($fbAdAccountId, null, null,
                        null, false, false, false, [
                            'field' => 'campaign.id',
                            'operator' => 'IN',
                            'value' => $campaignSourceIds
                        ]);
                } else {
                    $fetchAdsetJobs[]= new FacebookFetchAdsetV2($fbAdAccountId, null, $validatedData['date_start'],
                        $validatedData['date_stop'], true, true, true, [
                            'field' => 'campaign.id',
                            'operator' => 'IN',
                            'value' => $campaignSourceIds
                        ]);
                }
            }

        }

        if ( $mode === 1 || $mode === 4) {
            Bus::batch($insightJobs)->finally(function ($batch) {
                Log::debug("finished fetch campaign insights");
            })->onQueue('frontend')->allowFailures()->dispatch();
        }

        if ( $mode === 2 || $mode === 4) {
            Bus::batch($syncStatusjobs)->finally(function ($batch) {
                Log::debug("finished sync campaign status");
            })->onQueue('frontend')->allowFailures()->dispatch();
        }

        if ( $mode === 3 || $mode === 4) {
            Bus::batch($fetchAdsetJobs)->finally(function ($batch) {
                Log::debug("finished fetch adset");
            })->onQueue('frontend')->allowFailures()->dispatch();
        }

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language ),
            'success' => true
        ]);

    }

    public function sync_adset_insights(Request $request)
    {
        Log::debug("sync adset");
        $validatedData = $request->validate([
            'adset_ids' => 'array',
            'adset_ids.*' => 'string|exists:fb_adsets,id',
            'date_start' => 'required|date_format:Y-m-d|before_or_equal:date_stop',
            'date_stop' => 'required|date_format:Y-m-d|after_or_equal:date_start',
            'mode' => 'required|numeric|in:1,2,3,4'
        ]);

        $mode = $validatedData['mode'];
        Log::debug("sync mode: {$mode}");

        $date_start = Carbon::parse($validatedData['date_start']);
        $date_stop = Carbon::parse($validatedData['date_stop']);
        $days_diff = $date_stop->diffInDays($date_start);

        $user = $request->user();

        $campaignIds = $validatedData['adset_ids'];
        Log::debug("ids:", $campaignIds);
        $adsetWithAdAccount = FbAdset::query()->whereIn('id', $campaignIds)->with('fbAdAccount')
            ->get();
        $fbAdAccountIds = $adsetWithAdAccount->pluck('fbAdAccount.id')->unique()->toArray();
        Log::debug($fbAdAccountIds);

        foreach ($fbAdAccountIds as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

        $adsetsGrouped = $adsetWithAdAccount->groupBy(function ($campaign) {
            return $campaign->fbAdAccount->id; // 按 Ad Account ID 分组
        });
        // 处理结果，获取 AdAccount ID 和对应的 Campaign IDs
        $result = [];
        foreach ($adsetsGrouped as $adAccountId => $adset) {
            $result[$adAccountId] = $adset->pluck('source_id')->toArray(); // 获取该 Ad Account 下的 Campaign IDs
        }

        $syncStatusjobs = [];
        $insightJobs = [];
        $fetchAdJobs = [];

        foreach ($result as $fbAdAccountId => $adsetSourceIds) {
            if ( $mode===1 || $mode === 4) {
                $insightJobs[] = new FacebookFetchAdsetInsights($fbAdAccountId, $validatedData['date_start'],
                    $validatedData['date_stop'], null, false, [
                        'field' => 'adset.id',
                        'operator' => 'IN',
                        'value' => $adsetSourceIds
                    ]);
            }

            if ( $mode===2 || $mode === 4) {
//                $syncStatusjobs[] = new FacebookFetchAdset($fbAdAccountId, $validatedData['date_start'],
//                    $validatedData['date_stop'], null, false, false, 1, [
//                        'field' => 'id',
//                        'operator' => 'IN',
//                        'value' => $adsetSourceIds
//                    ]);
                $syncStatusjobs[] = new FacebookFetchAdsetV2($fbAdAccountId, null, null,
                    null, false, false, false,  [
                        'field' => 'id',
                        'operator' => 'IN',
                        'value' => $adsetSourceIds
                    ]);
            }

            if ( $mode===3 || $mode === 4) {
//                $fetchAdJobs[]= new FacebookFetchAd($fbAdAccountId, $validatedData['date_start'],
//                    $validatedData['date_stop'], null, false, true, $days_diff, [
//                        'field' => 'adset.id',
//                        'operator' => 'IN',
//                        'value' => $adsetSourceIds
//                    ]);
                if ($mode === 3) {
                    $fetchAdJobs[]= new FacebookFetchAdV2($fbAdAccountId, null,
                        null, null, false,  [
                            'field' => 'adset.id',
                            'operator' => 'IN',
                            'value' => $adsetSourceIds
                        ]);
                } else {
                    $fetchAdJobs[]= new FacebookFetchAdV2($fbAdAccountId, null,
                        $validatedData['date_start'], $validatedData['date_stop'], true,  [
                            'field' => 'adset.id',
                            'operator' => 'IN',
                            'value' => $adsetSourceIds
                        ]);
                }

            }

        }
        if ( $mode === 1 || $mode === 4) {
            Bus::batch($insightJobs)->finally(function ($batch) {
                Log::debug("finished fetch adset insights");
            })->onQueue('frontend')->allowFailures()->dispatch();
        }

        if ( $mode === 2 || $mode === 4) {
            Bus::batch($syncStatusjobs)->finally(function ($batch) {
                Log::debug("finished sync adset");
            })->onQueue('frontend')->allowFailures()->dispatch();
        }

        if ( $mode === 3 || $mode === 4) {
            Bus::batch($fetchAdJobs)->finally(function ($batch) {
                Log::debug("finished fetch ad");
            })->onQueue('frontend')->allowFailures()->dispatch();
        }


        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language ),
            'success' => true
        ]);

    }


    public function sync_ad_insights(Request $request)
    {
        Log::debug("sync ad");
        $validatedData = $request->validate([
            'ad_ids' => 'array',
            'ad_ids.*' => 'string|exists:fb_ads,id',
            'date_start' => 'required|date_format:Y-m-d|before_or_equal:date_stop',
            'date_stop' => 'required|date_format:Y-m-d|after_or_equal:date_start',
            'mode' => 'required|numeric|in:1,2,3,4'
        ]);

        $mode = $validatedData['mode'];
        Log::debug("sync mode: {$mode}");

        $user = $request->user();

        $adIds = $validatedData['ad_ids'];
        Log::debug("ids:", $adIds);
        $adsWithAdAccount = FbAd::query()->whereIn('id', $adIds)->with('fbAdAccountV2')
            ->get();
        $fbAdAccountIds = $adsWithAdAccount->pluck('fbAdAccountV2.id')->unique()->toArray();
        Log::debug($fbAdAccountIds);

        foreach ($fbAdAccountIds as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

        $adsGrouped = $adsWithAdAccount->groupBy(function ($ad) {
            return $ad->fbAdAccountV2->id; // 按 Ad Account ID 分组
        });
        // 处理结果，获取 AdAccount ID 和对应的 Campaign IDs
        $result = [];
        foreach ($adsGrouped as $adAccountId => $ad) {
            $result[$adAccountId] = $ad->pluck('source_id')->toArray(); // 获取该 Ad Account 下的 Campaign IDs
        }

        $syncStatusjobs = [];
        $insightJobs = [];

        foreach ($result as $fbAdAccountId => $adSourceIds) {
            if ( $mode===1 || $mode === 4) {
                $insightJobs[] = new FacebookFetchAdInsights($fbAdAccountId, $validatedData['date_start'],
                    $validatedData['date_stop'], null, false, [
                        'field' => 'ad.id',
                        'operator' => 'IN',
                        'value' => $adSourceIds
                    ]);
            }

            if ( $mode===2 || $mode === 4) {
//                $syncStatusjobs[] = new FacebookFetchAd($fbAdAccountId, $validatedData['date_start'],
//                    $validatedData['date_stop'], null, false, false, 1, [
//                        'field' => 'id',
//                        'operator' => 'IN',
//                        'value' => $adSourceIds
//                    ]);
                $syncStatusjobs[] = new FacebookFetchAdV2($fbAdAccountId, null, $validatedData['date_start'],
                    $validatedData['date_stop'], false, [
                        'field' => 'id',
                        'operator' => 'IN',
                        'value' => $adSourceIds
                    ]);
            }

        }
        if ( $mode === 1 || $mode === 4) {
            Bus::batch($insightJobs)->finally(function ($batch) {
                Log::debug("finished fetch ad insights");
            })->onQueue('frontend')->allowFailures()->dispatch();
        }

        if ( $mode === 2 || $mode === 4) {
            Bus::batch($syncStatusjobs)->finally(function ($batch) {
                Log::debug("finished sync ad");
            })->onQueue('frontend')->allowFailures()->dispatch();
        }


        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language ),
            'success' => true
        ]);

    }

    public function provider_spending_summary(Request $request)
    {
        $appName = env('APP_NAME');
//        if ($appName !== 'Gemini-kmh'  $appName !== 'Laravel') {
        if (!in_array($appName, ['Gemini-kmh', 'Laravel'])) {
            return response()->json(['error' => 'not available'], 400);
        }

        // 从环境变量中获取 API 密钥
        $key = env('GEMINI_GET_PROVIDER_SPENDING_API_KEY', '');

        // 从请求中提取传入的密钥
        $reqKey = $request->get('key');
        $days = $request->get('days', 31);

        // 验证传入的密钥是否正确
        if (!$key || $reqKey != $key) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        $currentMonth = now()->format('Y-m');
        $currentDay = now()->format('Y-m-d');

        $date = now()->subDays($days);
        $summary = [];

        // 定义提供商映射
        $providers = [
//            'provider_star' => 'P:Star',
//            'provider_aileway' => 'P:Aileway',
//            'provider_fuxi' => 'P:Fuxi'
            'provider_mc_cn2' => 'P:MC-CN2',
            'provider_atom_cl' => 'P:ATOM-CL',
            'provider_ysc_cn2' => 'P:YSC-CN2',
            'provider_ysc_bm' => 'P:YSC-BM',
            'provider_ysc_cl' => 'P:YSC-CL',
            'provider_ysc_tmi' => 'P:YSC-TMI',
        ];

        $month = $request->get('month');
        if ($month) {
            $date = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $currentDay = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->format('Y-m-d');
        }

        while ($date->format('Y-m-d') <= $currentDay) {
            // 初始化每天的摘要
            $dailySummary = [];

            foreach ($providers as $key => $providerName) {
                // Redis 的 key 格式是 "provider_spendings:{providerName}:{date}"
                $cacheKey = "provider_spendings:{$key}:{$date->format('Y-m-d')}";

                $spending = Redis::get($cacheKey);

                // 将结果格式化（假设格式化到小数点后三位）
                $dailySummary[$key] = number_format($spending, 3);
            }

            // 将每天的摘要存储在最终的 summary 数组中
            $summary[$date->format('Y-m-d')] = $dailySummary;

            $date->addDay();
        }

        return response()->json($summary);
    }

    public function cw_partner_spending_summary(Request $request)
    {
        $appName = env('APP_NAME');
        if (!in_array($appName, ['Laravel'])) {
            return response()->json(['error' => 'not available'], 400);
        }

        // 从环境变量中获取 API 密钥
        $key = env('GEMINI_GET_PROVIDER_SPENDING_API_KEY', '');

        // 从请求中提取传入的密钥
        $reqKey = $request->get('key');
        $days = $request->get('days', 7); // 默认7天

        // 验证传入的密钥是否正确
        if (!$key || $reqKey != $key) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        $currentDay = now()->format('Y-m-d');
        $date = now()->subDays($days);

        // 确保开始时间不早于2025-10-05
        $minStartDate = Carbon::parse('2025-11-05');
        if ($date->lt($minStartDate)) {
            $date = $minStartDate;
        }

        $summary = [];

        // 定义CW合作伙伴映射
        $cwPartners = [
            'cw_rt' => 'CW-RT',
            'cw_hq' => 'CW-HQ',
            'cw_wh' => 'CW-WH',
            'cw_ht' => 'CW-HT',
        ];

        $month = $request->get('month');
        if ($month) {
            $date = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $currentDay = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->format('Y-m-d');

            // 确保月份开始时间不早于2025-10-30
            if ($date->lt($minStartDate)) {
                $date = $minStartDate;
            }
        }

        while ($date->format('Y-m-d') <= $currentDay) {
            // 初始化每天的摘要
            $dailySummary = [];

            foreach ($cwPartners as $key => $partnerName) {
                // 从Redis获取各项指标
                $dateString = $date->format('Y-m-d');

                $spend = Redis::get("cw_partner_spend:{$key}:{$dateString}") ?: 0;
                $offerClicks = Redis::get("cw_partner_offer_clicks:{$key}:{$dateString}") ?: 0;
                $revenue = Redis::get("cw_partner_revenue:{$key}:{$dateString}") ?: 0;
                $offerCpc = Redis::get("cw_partner_offer_cpc:{$key}:{$dateString}") ?: 0;

                // 格式化数据
                $dailySummary[$key] = [
                    'partner_name' => $partnerName,
                    'spend' => number_format($spend, 3),
                    'offer_clicks' => intval($offerClicks),
                    'revenue' => number_format($revenue, 3),
                    'offer_cpc' => number_format($offerCpc, 4),
                ];
            }

            // 将每天的摘要存储在最终的 summary 数组中
            $summary[$date->format('Y-m-d')] = $dailySummary;

            $date->addDay();
        }

        return response()->json($summary);
    }

    public function update_bid_amount(Request $request)
    {
        // TODO: 权限管理
        $validatedData = $request->validate([
            'id' => 'string',
            'value' => 'numeric|gt:0',
        ]);

        $id = $validatedData['id'];
        $value = $validatedData['value'];

        if (!DevUtils::exists(FbAdset::class, $id)) {
            return response()->json(['message' => 'Adset does not exist'], 403);
        } else {
            $fbAdAccount = FbAdset::query()->firstWhere('id', $id)->fbAdAccount;
        }

        $user = $request->user();
        if (!$user->can('operate', $fbAdAccount)) {
            Log::debug("can not operate");
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        FacebookUpdateBidAmount::dispatch($fbAdAccount, $id, $value)->onQueue('frontend');

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ]);

    }

    public function update_bid_strategy(Request $request)
    {
        // 修改 bid strategy 分几种情况
        // 如果是 adset 的，分 2 种，high volume: 直接修改就行，cpa 和 bidcap: 要加一个 bid_amount 字段
        // 如果是 campaign 的，分 2 种情况，high volume: 直接修改，cpa 和 bid cap 需要传 map, adset id => bid_amount value
        $validatedData = $request->validate([
            'id' => 'string',
            'object_value' => 'nullable|numeric|gt:0',
            'object_type' => 'string|in:campaign,adset',
            'bid_strategy' => 'string|in:LOWEST_COST_WITHOUT_CAP,LOWEST_COST_WITH_BID_CAP,COST_CAP'
        ]);

        $id = $validatedData['id'];
        $object_type = $validatedData['object_type'];
        $object_value = $validatedData['object_value'] ?? 0;
        $bid_strategy = $validatedData['bid_strategy'];

        if ($object_type === 'campaign') {
            if (!DevUtils::exists(FbCampaign::class, $id)) {
                return response()->json(['message' => 'Campaign does not exist'], 403);
            } else {
                $fbAdAccount = FbCampaign::query()->firstWhere('id', $id)->fbAdAccount;
            }
        } else if ($object_type === 'adset') {
            if (!DevUtils::exists(FbAdset::class, $id)) {
                return response()->json(['message' => 'Adset does not exist'], 403);
            } else {
                $fbAdAccount = FbAdset::query()->firstWhere('id', $id)->fbAdAccount;
            }
        }

        FacebookUpdateBidStrategy::dispatch($fbAdAccount, $bid_strategy, $object_type, $id, $object_value)
            ->onQueue('frontend');

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ]);
    }

    public function delte_objects(Request $request)
    {
        $validatedData = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string',
            'type' => 'required|string|in:campaign,adset,ad'
        ]);
        $ids = $validatedData['ids']; // 从请求中获取要归档的账户ID
        $user = $request->user();

        $validStatus = [
            'ACTIVE', 'PAUSED', 'IN_PROCESS', 'WITH_ISSUES',
            'PENDING_REVIEW', 'CAMPAIGN_PAUSED', 'ADSET_PAUSED',
        ];
        $object_type = $validatedData['type'];
        if ($object_type === 'campaign') {
            $cleanSourceIds = FbCampaign::query()->whereIn('id', $ids)->whereIn('status', $validStatus)->pluck('source_id');
            $adAccounts = FbAdAccount::whereHas('fbCampaigns', function($query) use ($cleanSourceIds, $ids) {
                $query->whereIn('fb_campaigns.source_id', $cleanSourceIds);
            })->get();
        } elseif ($object_type === 'adset') {
            $cleanSourceIds = FbAdset::query()->whereIn('id', $ids)->whereIn('status', $validStatus)->pluck('source_id');
            $adAccounts = FbAdAccount::whereHas('fbAdsets', function($query) use ($cleanSourceIds, $ids) {
                $query->whereIn('fb_adsets.source_id', $cleanSourceIds);
            })->get();
        } elseif ($object_type === 'ad') {
            $cleanSourceIds = FbAd::query()->whereIn('id', $ids)->whereIn('status', $validStatus)->pluck('source_id');
            $adAccounts = FbAdAccount::whereHas('fbAds', function($query) use ($cleanSourceIds, $ids) {
                $query->whereIn('fb_ads.source_id', $cleanSourceIds);
            })->get();
        }

        foreach ($adAccounts as $adAccount) {
            if (!$user->can('operate', $adAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

        foreach ($cleanSourceIds as $index => $sourceId) {
            FacebookDeleteAdObject::dispatch($sourceId, $object_type)->delay($index * 5)->onQueue('frontend');
        }


        return response()->json(['message' => 'Task submitted']);
    }

    /**
     * CBO 到 ABO 转换接口（批量）
     */
    public function cbo_2_abo(Request $request)
    {
        $validatedData = $request->validate([
            'campaign_source_ids' => 'required|array',
            'campaign_source_ids.*' => 'required|string',
            'budget' => 'required|numeric|min:1'
        ]);

        $campaignSourceIds = $validatedData['campaign_source_ids'];
        $budget = $validatedData['budget']; // 美元金额
        $user = $request->user();

        // 查找所有 campaigns 并检查是否存在
        $campaigns = FbCampaign::whereIn('source_id', $campaignSourceIds)
            ->with('fbAdAccount')
            ->get();

        if ($campaigns->count() !== count($campaignSourceIds)) {
            $foundIds = $campaigns->pluck('source_id')->toArray();
            $missingIds = array_diff($campaignSourceIds, $foundIds);
            return response()->json([
                'message' => 'Some campaigns not found',
                'missing_campaign_source_ids' => $missingIds
            ], 404);
        }

        // 收集所有相关的广告账户并检查权限
        $allAdAccounts = $campaigns->pluck('fbAdAccount')->filter()->unique('id');

        if ($allAdAccounts->isEmpty()) {
            return response()->json(['message' => 'No valid ad accounts found for these campaigns'], 404);
        }

        // 检查用户对所有广告账户的权限
        foreach ($allAdAccounts as $adAccount) {
            if (!$user->can('operate', $adAccount)) {
                return response()->json([
                    'message' => 'Unauthorized action for ad account: ' . $adAccount->source_id
                ], 403);
            }
        }

        // 验证每个 campaign 下都有有效的 adsets
        $campaignDetails = [];
        foreach ($campaigns as $campaign) {
            $validAdsets = FbAdset::where('fb_campaign_id', $campaign->id)
                ->whereNotIn('status', ['ARCHIVED', 'DELETED'])
                ->count();

            if ($validAdsets === 0) {
                return response()->json([
                    'message' => 'No valid adsets found for campaign: ' . $campaign->source_id
                ], 400);
            }

            $campaignDetails[] = [
                'campaign_source_id' => $campaign->source_id,
                'adsets_count' => $validAdsets,
                'ad_account_id' => $campaign->fbAdAccount->source_id
            ];
        }

        Log::info("提交批量 CBO 到 ABO 转换任务", [
            'campaign_source_ids' => $campaignSourceIds,
            'campaigns_count' => count($campaignSourceIds),
            'budget' => $budget,
            'user_id' => $user->id
        ]);

        // 为每个 campaign 提交异步任务，按广告账户分组来避免API频率限制
        $tasksByAdAccount = [];
        foreach ($campaigns as $campaign) {
            $adAccountId = $campaign->fbAdAccount->id;
            if (!isset($tasksByAdAccount[$adAccountId])) {
                $tasksByAdAccount[$adAccountId] = [];
            }
            $tasksByAdAccount[$adAccountId][] = $campaign->source_id;
        }

        // 提交按广告账户分组的任务
        foreach ($tasksByAdAccount as $accountTasks) {
            $delaySeconds = 0;
            foreach ($accountTasks as $campaignSourceId) {
                FacebookCboToAbo::dispatch($campaignSourceId, $budget)
                    ->onQueue('frontend')
                    ->delay($delaySeconds);

                $delaySeconds += 20; // 同一个广告账户的任务间隔30秒
            }
        }

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true,
            'details' => [
                'campaigns_count' => count($campaignSourceIds),
                'budget' => $budget,
                'ad_accounts_count' => count($tasksByAdAccount),
                'campaigns' => $campaignDetails,
                'tasks_by_ad_account' => array_map(function($tasks) {
                    return [
                        'campaigns_count' => count($tasks),
                        'max_delay_seconds' => (count($tasks) - 1) * 30
                    ];
                }, $tasksByAdAccount)
            ]
        ]);
    }
}
