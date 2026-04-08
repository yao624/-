<?php

namespace App\Jobs;

use App\Models\FbAccount;
use App\Models\FbApiToken;
use App\Models\FbPage;
use App\Models\FbPagePost;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FacebookFetchPagePost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $page_source_id;
    private $post_source_id;
    private $ad_account_source_id;
    private $campaign_source_id;
    private $adset_source_id;
    private $ad_source_id;
    private $ad_creative;
    /**
     * Create a new job instance.
     */
    public function __construct($page_source_id, $post_source_id, $ad_account_source_id,
                                $campaign_source_id, $adset_source_id, $ad_source_id, $ad_creative)
    {
        $this->page_source_id = $page_source_id;
        $this->post_source_id = $post_source_id;
        $this->ad_account_source_id = $ad_account_source_id;
        $this->campaign_source_id = $campaign_source_id;
        $this->adset_source_id = $adset_source_id;
        $this->ad_source_id = $ad_source_id;
        $this->ad_creative = $ad_creative;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 检查 Page, Page token, 获取 Page token, 拉取数据，保存数据
        // if ($this->post_source_id == '122283471950006658' || $this->post_source_id == '122275103486026934') {
        //     return;
        // }
        if (in_array($this->post_source_id, ['122283471950006658', '122275103486026934','1304873244971611',
            '122258620376016222', '122210798654105144', '122289970700006658', '122259485672027312',
            '122298135212008890','122298135212008890', '122229860510102730', '122226051488090696',
            '122229860492102730', '122236297610103788', '122229860546102730', '122236297568103788'])) {
            return;
        }

        Log::debug("fetch page post: {$this->page_source_id}, {$this->post_source_id}");
        /** @var FbPage $page */
        $page = FbPage::query()->where('source_id', $this->page_source_id)->firstOrFail();

        $fbAccount = null;
        $page_token = '';

        // 依次检查 tokens, 如果 owner 是 fb ,则传 fbAccount 和 page token
        // 否则只传 page token
        $tokens = collect($page->tokens);
        foreach ($tokens as $t) {
            $ownerId = $t['owner_id'];
            $ownerType = $t['owner_type'];
            $tk_str = $t['token'];
            if ($ownerType === 'fb') {
                $acc = FbAccount::query()->where('token_valid', true)->where('id', $ownerId)->first();
                if ($acc) {
                    $fbAccount = $acc;
                    $page_token = $tk_str;
                    break;
                }
            } elseif ($ownerType === 'bm') {
                $apiToken = FbApiToken::query()->where('id', $ownerId)->where('active', true)->first();
                if ($apiToken) {
                    $page_token = $tk_str;
                }
            }
        }

        if (!$fbAccount && !$page_token) {
            $msg = "Can not get page token: {$page->source_id}";
            Log::warning($msg);
//            Telegram::sendMessage($msg);
            return;
        }

        $query = [
            'fields' => 'id,created_time,permalink_url,message,attachments{title,description,type,unshimmed_url,media}',
        ];
        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/{$this->page_source_id}_{$this->post_source_id}";

        // get page post 有点特别，它需要的是 page token, 但是 page token 可以用 bm token 获取，也可以用  fb account 获取
        // 用  fb account 获取的，在发起请求时，需要用相同的 proxy, cookie
        $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'GET', null, 'get-page-post', $page_token);
        Log::debug($resp);

        $primary_text = $resp['message'] ?? '';
        $headline = '';
        $description = '';
        $post_type = $resp['type'] ?? '';
        $url = '';
        $permalink_url = $resp['permalink_url'] ?? '';
        $media = [];
        $url_tags = '';

        if (isset($resp['attachments'])) {
            $attachment = collect(collect($resp['attachments'])->get('data', []));
            if ($attachment->first()) {
                $first = $attachment->first();
                $headline = $first['title'] ?? '';
                $description = $first['description'] ?? '';
                $url = $first['unshimmed_url'] ?? '';
                $media = $first['media'] ?? [];
            }
        }

        // 如果有视频广告，链接是从这里面来的
        if ($this->ad_creative) {
            $url_from_ads1 = $this->ad_creative['object_story_spec']['video_data']['call_to_action']['value']['link'] ?? '';
            if ($url_from_ads1) {
                $url = $url_from_ads1;
            }

            $url_from_ads2 = $this->ad_creative['object_story_spec']['link_data']['link'] ?? '';
            if ($url_from_ads2) {
                $url = $url_from_ads2;
            }

            $url_tags = $this->ad_creative['url_tags'] ?? '';

        } else {
            Log::warning("creative is empty");
        }

        if ($resp['success']) {
            FbPagePost::query()->updateOrCreate(
                [
                    'source_id' => $this->post_source_id
                ],
                [
                'primary_text' => $primary_text,
                'headline' => $headline,
                'description' => $description,
                'post_type' => $post_type,
                'url' => $url,
                'url_tags' => $url_tags,
                'permalink_url' => $permalink_url,
                'created_time' => $resp['created_time'],
                'campaign_source_id' => $this->campaign_source_id,
                'adset_source_id' => $this->adset_source_id,
                'ad_source_id' => $this->ad_source_id,
                'page_source_id' => $this->page_source_id,
                'ad_account_source_id' => $this->ad_account_source_id,
                'media' => $media,
            ]);
        }

    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('FacebookFetchPagePost Job failed: ' . $exception->getMessage());
    }

    public function tags(): array
    {
        return [
            'FB-Fetch-Post',
            "{$this->page_source_id}:{$this->post_source_id}",
            "{$this->ad_account_source_id}-{$this->campaign_source_id}-{$this->adset_source_id}-{$this->ad_source_id}",
        ];
    }
}
