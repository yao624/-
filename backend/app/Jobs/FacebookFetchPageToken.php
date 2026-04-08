<?php

namespace App\Jobs;

use App\Models\FbPage;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FacebookFetchPageToken implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    private $page_id;
    /**
     * Create a new job instance.
     */
    public function __construct($page_id)
    {
        // 有两种方式获取，使用 FbAccount 的短效 token, 使用 FBApiToken。先使用 FbAccount token
        $this->page_id = $page_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fbPage = FbPage::query()->where('id', $this->page_id)->firstOrFail();

        $fbAccount = $fbPage->fbAccounts()->where('token_valid', true)->first();
        $endpoint = "https://graph.facebook.com/{$fbPage->source_id}";
        $query = [
            'fields' => 'access_token',
        ];

        if ($fbAccount) {
            // 通过 FbAccount 获取 page token

            $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'GET', null, null, null);
            Log::debug($resp);
            if ($resp['success']) {
                if (isset($resp['access_token']) && $resp['access_token']) {
                    $token = $resp['access_token'];

                    $tokens = $fbPage->tokens ?? [];
                    $ownerId = $fbAccount->id;
                    $ownerType = 'fb';
                    $this->extracted($ownerId, $tokens, $token, $ownerType, $fbPage);
                }
            }
        } else {
            Log::info("use bm token");
            $fbApiToken = $fbPage->fbApiTokens()->firstWhere('active', true);
            if ($fbApiToken) {
                $resp = FbUtils::makeRequest(null, $endpoint, $query, 'GET', null, null,
                    $fbApiToken['token']);
                Log::debug($resp);
                if ($resp['success']) {
                    if (isset($resp['access_token']) && $resp['access_token']) {

                        $token = $resp['access_token'];

                        $tokens = $fbPage->tokens ?? [];
                        $ownerId = $fbApiToken->id;
                        $ownerType = 'bm';
                        $this->extracted($ownerId, $tokens, $token, $ownerType, $fbPage);
                    }
                }
            } else {
                $msg = "No valid token for page: {$fbPage['source_id']}";
                Log::warning($msg);
                Telegram::sendMessage($msg);
            }
        }
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('FacebookFetchPageToken Job failed: ' . $exception->getMessage());
    }

    public function tags(): array
    {
        return [
            'FB-Fetch-Page-Token',
            "{$this->page_id}",
        ];
    }

    /**
     * @param $ownerId
     * @param mixed $tokens
     * @param mixed $token
     * @param string $ownerType
     * @param \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $fbPage
     * @return void
     */
    public function extracted($ownerId, mixed $tokens, mixed $token, string $ownerType, $fbPage): void
    {
        $existingKey = array_search($ownerId, array_column($tokens, 'owner_id'));
        if ($existingKey !== false) {
            // 如果已存在，则更新对应的 token
            $tokens[$existingKey]['token'] = $token;
        } else {
            // 如果不存在，则插入新的 token
            $tokens[] = [
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'token' => $token,
            ];
        }

        $fbPage->tokens = $tokens;
        $fbPage->save();
    }
}
