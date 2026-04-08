<?php

namespace App\Jobs;

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

class FacebookFetchAd implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    private $timeout = 600;

    private $fbAdAccountID;
    private $adAccount;
    private $fbAdAccountSoruceID;

    private mixed $date_stop;
    private mixed $date_start;
    private $next;
    private mixed $fbAccountID;
    private $fbAccount;
    private $token;
    private $all;
    private $lastDays;
    private array $filtering;

    /**
     * Create a new job instance.
     */
    public function __construct($fbAdAccountID, $date_start=null, $date_stop=null, $fbAccountID=null, $next=true,
                                $all=false, $lastDays=1, $filtering=[])
    {
        $this->fbAdAccountID = $fbAdAccountID;
        $this->adAccount = FbAdAccount::query()->findOrFail($fbAdAccountID);
        $this->fbAdAccountSoruceID = $this->adAccount->source_id;
        $this->date_start = $date_start;
        $this->date_stop = $date_stop;
        $this->next = $next;
        $this->fbAccountID = $fbAccountID;
        $this->all = $all;
        $this->lastDays = $lastDays;
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
            $apiToken = $this->adAccount->apiTokens()->firstWhere('active', true);
            if ($apiToken) {
                $token = $apiToken['token'];
                Log::info("use api token");
                $this->token = $token;
            } else {
                $query = $this->adAccount->fbAccounts()->where('token_valid', true);
                if ($query->count() == 0) {
                    Log::warning("no api token, nor fb account");
                    $msg = "{$this->fbAdAccountSoruceID} no api token or fb account available";
                    Telegram::sendMessage($msg);
                    throw new \Exception("no api token, nor fb account");
                } else {
                    $this->fbAccount = $query->first();
                    $this->fbAccountID = $this->fbAccount->id;
                }
            }
        } else {
            # 查找到一个 token valid 是有效的fb account

            $this->fbAccount = $this->adAccount->fbAccounts()->where('token_valid', true)->firstOrFail();
            $this->fbAccountID = $this->fbAccount->id;
        }

        Log::info("--- Fetch FB Ad Data, Ad Account: {$this->fbAdAccountSoruceID}");

        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/act_{$this->fbAdAccountSoruceID}/ads";
        $fields = 'id,account_id, ad_active_time,adset_id,campaign_id, configured_status,created_time,creative{body, title, id,actor_id,call_to_action_type, effective_instagram_story_id,effective_object_story_id,instagram_permalink_url,instagram_story_id,link_url,object_story_id,object_id,object_story_spec,object_url,status,thumbnail_url, object_type,link_destination_display_url, object_store_url,url_tags,product_set_id},effective_status,name,preview_shareable_link,source_ad_id,status, updated_time';
        $page_limit = 50;

        $query = [
            'fields' => $fields,
            'limit' => $page_limit,
            'filtering' => [
                [
                    'field' => 'ad.effective_status',
                    'operator' => 'IN',
                    'value' => ['ACTIVE', 'PAUSED', 'DELETED', 'ARCHIVED', 'IN_PROCESS', 'WITH_ISSUES',
                        'CAMPAIGN_PAUSED', 'ADSET_PAUSED', 'PENDING_REVIEW'],
                ],
            ],
        ];

        if ($this->all) {
            if ($this->date_start) {
                $query['filtering'][] = [
                    'field' => 'created_time',
                    'operator' => 'GREATER_THAN',
                    'value' => Carbon::parse($this->date_start)->timestamp,
                ];
                if ($this->date_stop) {
                    $query['filtering'][] = [
                        'field' => 'created_time',
                        'operator' => 'LESS_THAN',
                        'value' => Carbon::parse($this->date_stop)->timestamp,
                    ];
                }
            } else {
                $timestamp = Carbon::now()->subDays($this->lastDays)->timestamp;
                $query['filtering'][] = [
                    'field' => 'created_time',
                    'operator' => 'GREATER_THAN',
                    'value' => $timestamp,
                ];
            }

        } else {
            if ($this->date_start) {
                $query['filtering'][] = [
                    'field' => 'created_time',
                    'operator' => 'GREATER_THAN',
                    'value' => Carbon::parse($this->date_start)->timestamp,
                ];
                if ($this->date_stop) {
                    $query['filtering'][] = [
                        'field' => 'created_time',
                        'operator' => 'LESS_THAN',
                        'value' => Carbon::parse($this->date_stop)->timestamp,
                    ];
                }
            } else {
                $last30days =  Carbon::now()->subDays(30)->timestamp;
                $query['filtering'][] = [
                    'field' => 'created_time',
                    'operator' => 'GREATER_THAN',
                    'value' => $last30days,
                ];
            }

        }

        if (!empty($this->filtering)) {
            $query['filtering'][] = $this->filtering;
        }
//        Log::debug($query);

        $resp = FbUtils::makeRequest($this->fbAccount, $endpoint, $query, 'GET', null, '', $token);
//        Log::debug("resp json: {$resp->toJson()}");

        $paging = collect($resp->get('paging'));
        $ad_ids = collect();
        $ids = $this->processResponse($resp);
        $ad_ids = $ad_ids->concat($ids);
        while ($paging->has('next')) {
            Log::info("--- Fetch FB Ad Data, Ad Account: {$this->fbAdAccountSoruceID} new page");
            $next = $paging->get('next');
            $resp = FbUtils::makeRequest($this->fbAccount, $next, null, 'GET', null, '', $token);
//            Log::debug("response");
//            Log::debug($resp);
            $ids = $this->processResponse($resp);
            $ad_ids = $ad_ids->concat($ids);
            $paging = collect($resp->get('paging'));
        }
    }

    private function processResponse(Collection $resp)
    {
        $ad_source_ids = collect();
        $adsData = collect($resp->get('data',[]));
        Log::debug("ads data:");
        Log::debug($adsData);

        $postSourceIds = $adsData->map(function ($item) {
            $effectiveId = $item['creative']['effective_object_story_id'] ?? null;
            return $effectiveId ? explode('_', $effectiveId)[1] : null;
        })->filter()->unique()->values();

        // 已经存在于系统中的 Post, 不需要再获取 Posts 了
        $posts = FbPagePost::query()->whereIn('source_id', $postSourceIds)->pluck('source_id');
        Log::debug("post list");
        Log::debug($posts);

        $adsData->each(function ($adData) use ($posts, &$ad_source_ids) {


            $fbCampaign = FbCampaign::query()->firstWhere('source_id', $adData['campaign_id']);
            $fbAdset = FbAdset::query()->firstWhere('source_id', $adData['adset_id']);
            if (!$fbCampaign || !$fbAdset) {
                Log::warning("campaign not in system, campaign id {$adData['campaign_id']}, adset id: {$adData['adset_id']}");
                return;
            }

            $ad_source_ids->push($adData['id']);
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
                    FacebookFetchPagePost::dispatch($pageSourceId, $postSourceId, $adAccountSourceId, $campaignSourceId,
                    $adsetSourceId, $adSourceId, $adData['creative'] ?? [])->onQueue('facebook');
                }
            }



        });

        return $ad_source_ids;
    }
}
