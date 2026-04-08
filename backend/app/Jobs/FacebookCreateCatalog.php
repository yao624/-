<?php

namespace App\Jobs;

use App\Models\FbBm;
use App\Models\FbCatalog;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FacebookCreateCatalog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $bm_id;
    private string $name;
    /**
     * Create a new job instance.
     */
    public function __construct(string $bm_id, string $name)
    {
        $this->bm_id = $bm_id;
        $this->name = $name;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 根据 BM id，查询 token type 为 1 的 token
        $bm = FbBm::query()->firstWhere('id', $this->bm_id);
        $fbApiToken = $bm->fbApiTokens()->where('token_type', 1)->where('active', true)->firstOrFail();
//        $fbApiToken = $this->fbAdAccount->apiTokens()->where('active', true)->firstOrFail();
        Log::debug($fbApiToken->token);

        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/{$bm->source_id}/owned_product_catalogs";

        $body = [
            "name" => $this->name,
            "vertical" => 'commerce',
        ];
        Log::debug($body);

        $resp = FbUtils::makeRequest(null, $endpoint, null, 'POST', $body, '', $fbApiToken->token);
        if ($resp['success']) {
            Log::debug("Create catalog success, bm id: {$bm->source_id}, catalog id: {$resp['id']}");
        } else {
            $message = "Create catalog failed\r\n bm id:{$bm->source_id}";
            Log::warning($message);
        }
    }

    public function tags(): array
    {
        return [
            "FB-Create-Catalog",
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Create catalog failed: ' . $exception->getMessage());
        $message = "Create catalog failed\r\n bm id: {$this->bm_id}";
        Telegram::sendMessage($message);
    }
}
