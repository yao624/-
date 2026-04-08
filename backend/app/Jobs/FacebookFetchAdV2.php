<?php

namespace App\Jobs;

use App\Models\FbAccount;
use App\Models\FbAd;
use App\Models\FbAdAccount;
use App\Models\FbAdset;
use App\Models\FbCampaign;
use App\Models\FbPage;
use App\Models\FbPagePost;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FacebookFetchAdV2 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    private $timeout = 2600;

    private FbAdAccount|null $adAccount;
    private FbAccount|null $fbAccount;
    private string $fbAdAccountID;
    private string $fbAdAccountSourceID;
    private string|null $fbAccountID;

    private mixed $date_stop;
    private mixed $date_start;
    private string $currency;
    private string $token;
    private bool $pull_insights;
    private array $filtering;
    /**
     * Create a new job instance.
     */
    public function __construct($fbAdAccountID, $fbAccountID=null, $date_start=null, $date_stop=null,
                                $pull_insights=false, $filtering=[])
    {
        $this->fbAdAccountID = $fbAdAccountID;
        $this->adAccount = FbAdAccount::query()->findOrFail($fbAdAccountID);
        $this->fbAdAccountSourceID = $this->adAccount->source_id;
        $this->currency = $this->adAccount->currency;

        $this->fbAccountID = $fbAccountID;
        $this->date_start = $date_start;
        $this->date_stop = $date_stop;

        $this->pull_insights = $pull_insights;
        $this->filtering = $filtering;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $token = '';
        if ($this->fbAccountID == null) {
            Log::debug("fb account id is null");
            $this->fbAccount = null;
            $apiToken = $this->adAccount->apiTokens()->firstWhere('active', true);
            if ($apiToken) {
                $token = $apiToken->token;
                $this->token = $token;
            } else {
                // 重新查找 token 有效的 fb account
                $fbAccount = $this->adAccount->fbAccounts()->where('token_valid', true)->first();
                if ($fbAccount) {
                    $this->fbAccount = $fbAccount;
                    $this->fbAccountID = $this->fbAccount->id;
                } else {
                    Log::warning("no api token, nor fb account");
                    $msg = "{$this->adAccount->source_id} no api token or fb account available";
                    Telegram::sendMessage($msg);
                    throw new \Exception("no api token, nor fb account");
                }
            }
        } else {
            $this->fbAccount = $this->adAccount->fbAccounts()->where('token_valid', true)->firstOrFail();
            $this->fbAccountID = $this->fbAccount->id;
        }

        Log::info("--- Fetch FB Ad Data, Ad Account: {$this->fbAdAccountSourceID}");

        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/act_{$this->fbAdAccountSourceID}/ads";
        $fields = 'id,account_id, ad_active_time,adset_id,campaign_id, configured_status,created_time,creative{body,title,id,actor_id,call_to_action_type,effective_object_story_id,instagram_permalink_url,object_type,object_story_spec,status,thumbnail_url,url_tags,image_hash,video_id,asset_feed_spec,product_set_id,template_url_spec},effective_status,name,preview_shareable_link,source_ad_id,status, updated_time';
        $page_limit = 1;

        $query = [
            'fields' => $fields,
            'limit' => $page_limit,
            'filtering' => [
                [
                    'field' => 'ad.effective_status',
                    'operator' => 'IN',
                    'value' => [
                        'ACTIVE', 'PAUSED', 'DELETED', 'PENDING_REVIEW', 'DISAPPROVED', 'PREAPPROVED',
                        'PENDING_BILLING_INFO', 'CAMPAIGN_PAUSED', 'ARCHIVED', 'ADSET_PAUSED', 'IN_PROCESS', 'WITH_ISSUES',
                    ],
                ]
            ]
        ];

                        // 添加FbAdAccount的filters（优先级高于代码中的filtering）
        if ($this->adAccount->filters) {
            $accountFilters = $this->adAccount->filters;
            foreach ($accountFilters as $accountFilter) {
                // 检查scope是否包含ad
                if (!isset($accountFilter['scope']) || !in_array('ad', $accountFilter['scope'])) {
                    continue;
                }

                $filterForFb = [
                    'field' => $accountFilter['field'],
                    'operator' => $accountFilter['operator'],
                    'value' => $accountFilter['value']
                ];

                // 检查是否与现有filtering重复，如果重复则替换
                $replaced = false;
                foreach ($query['filtering'] as $index => $existingFilter) {
                    if ($existingFilter['field'] === $accountFilter['field']) {
                        $query['filtering'][$index] = $filterForFb; // 替换为ad account的filter
                        $replaced = true;
                        break;
                    }
                }

                // 如果没有重复，则添加
                if (!$replaced) {
                    $query['filtering'][] = $filterForFb;
                }
            }
        }

        if (!empty($this->filtering)) {
            $query['filtering'][] = $this->filtering;
        }

        $resp = FbUtils::makeRequest($this->fbAccount, $endpoint, $query, 'GET', null, '', $token);
        $paging = collect($resp->get('paging'));
        $this->processResponse($resp);

        while ($paging->has('next')) {
            Log::info("--- Fetch FB Ad Data, Ad Account: {$this->fbAdAccountSourceID} next page");
            $next = $paging->get('next');
            $resp = FbUtils::makeRequest($this->fbAccount, $next, null, 'GET', null, '', $token);
            $this->processResponse($resp);
            $paging = collect($resp->get('paging'));
        }

        if ($this->pull_insights) {
            FacebookFetchAdInsights::dispatch($this->fbAdAccountID, $this->date_start, $this->date_stop,
                $this->fbAccountID )->onQueue('facebook');
        }
    }

    private function processResponse(array|Collection $resp)
    {
        $source_ids = collect();
//        Log::debug(json_encode($resp));
        $adsData = collect($resp->get('data',[]));

        $postSourceIds = $adsData->map(function ($item) {
            $effectiveId = $item['creative']['effective_object_story_id'] ?? null;
            return $effectiveId ? explode('_', $effectiveId)[1] : null;
        })->filter()->unique()->values();

        $posts = FbPagePost::query()->whereIn('source_id', $postSourceIds)->pluck('source_id');
        Log::debug("post list");
        Log::debug($posts);

        $fbCampaignSourceIds = $adsData->pluck('campaign_id');
        $existingCampaign = FbCampaign::query()->whereIn('source_id', $fbCampaignSourceIds)->get();
        $fbAdsetSourceIds = $adsData->pluck('adset_id');
        $existingAdset = FbAdset::query()->whereIn('source_id', $fbAdsetSourceIds)->get();

        $post_data_pairs = collect();
        $adsData->each(function ($adData) use ($post_data_pairs, $existingAdset, $existingCampaign, $posts, &$source_ids) {

            $fbCampaign = $existingCampaign->firstWhere('source_id',$adData['campaign_id']);
            $fbAdset = $existingAdset->firstWhere('source_id',$adData['adset_id']);

            if (!$fbCampaign || !$fbAdset) {
                Log::warning("campaign not in system, campaign id {$adData['campaign_id']}, adset id: {$adData['adset_id']}");
                return;
            }

            $source_ids->push($adData['id']);
            $fbAd = FbAd::query()->updateOrCreate(
                [
                    'source_id' => $adData['id']
                ],
                [
                    'fb_ad_account_id' => $adData['account_id'],
                    'fb_campaign_id' => $fbCampaign->id,
                    'fb_adset_id' => $fbAdset->id,
                    'adset_id' => $adData['adset_id'],
                    'campaign_id' => $adData['campaign_id'],
                    'configured_status' => $adData['configured_status'],
                    'created_time' => $adData['created_time'] ? Carbon::parse($adData['created_time']) : null,
                    'creative' => $adData['creative'] ?? [],
                    'effective_status' => $adData['effective_status'],
                    'name' => $adData['name'],
                    'preview_shareable_link' => $adData['preview_shareable_link'] ?? '',
                    'source_ad_id' => $adData['source_ad_id'],
                    'status' => $adData['status'],
                    'post_url' => '',
                    'updated_time' => $adData['updated_time'] ? Carbon::parse($adData['updated_time']) : null,
                ]
            );

            if (isset($adData['creative']) && isset($adData['creative']['object_story_spec']) && isset($adData['creative']['object_story_spec']['page_id'])) {
                $fb_page_source_id = $adData['creative']['object_story_spec']['page_id'];
                $fbPage = FbPage::where('source_id', $fb_page_source_id)->first();
                if ($fbPage) {
                    $fbAd->fbPages()->syncWithoutDetaching([
                        $fbPage->id => ['fb_page_source_id' => $fb_page_source_id]
                    ]);
                }
            }

            $effectiveId = $adData['creative']['effective_object_story_id'] ?? null;
            $postSourceId = $effectiveId ? explode('_', $effectiveId)[1] : null;
            $pageSourceId = $effectiveId ? explode('_', $effectiveId)[0] : null;
            Log::debug("page post: {$pageSourceId}_{$postSourceId}");

            if ($pageSourceId && $postSourceId) {
                $adAccountSourceId = $adData['account_id'];
                $campaignSourceId = $adData['campaign_id'];
                $adsetSourceId = $adData['adset_id'];
                $adSourceId = $adData['id'];

                if (!$posts->contains($postSourceId)) {
                    $pair = [$pageSourceId, $postSourceId, $adAccountSourceId, $campaignSourceId,
                        $adsetSourceId, $adSourceId, $adData['creative'] ?? []];
                    $post_data_pairs->add($pair);
//                    FacebookFetchPagePost::dispatch($pageSourceId, $postSourceId, $adAccountSourceId, $campaignSourceId,
//                        $adsetSourceId, $adSourceId, $adData['creative'] ?? [])->onQueue('facebook');
                }
            }
        });

        $page_source_ids = $post_data_pairs->map(function ($item) {
            // 每个子数组第一个元素是 page_source_id
            return $item[0];
        });

        $pages = FbPage::query()->whereIn('source_id', $page_source_ids->toArray())->get();

        $post_data_pairs->each(function ($data_pair) use ($post_data_pairs, $pages) {
            $page_source_id = $data_pair[0];
            $page = $pages->firstWhere('source_id', $page_source_id);
            if ($page) {
                FacebookFetchPagePost::dispatch($page_source_id, $data_pair[1], $data_pair[2], $data_pair[3],
                    $data_pair[4], $data_pair[5], $data_pair[6])->onQueue('facebook');
            }
        });


    }

    public function tags(): array
    {
        return [
            'FB-Pull-Ad',
            "{$this->fbAdAccountSourceID}",
            "{$this->fbAccountID}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('FacebookFetchAd Job failed: ' . $exception->getMessage());
    }

}
