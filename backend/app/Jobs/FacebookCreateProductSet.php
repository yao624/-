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

class FacebookCreateProductSet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $bm_id;
    private string $catalog_id;
    private array $product_set;
    /**
     * Create a new job instance.
     */
    public function __construct(string $bm_id, string $catalog_id, array $product_set)
    {
        $this->bm_id = $bm_id;
        $this->catalog_id = $catalog_id;
        $this->product_set = $product_set;
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

        $catalog = FbCatalog::query()->where('id', $this->catalog_id)->firstOrFail();

        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/{$catalog->source_id}/product_sets";

        $body = [
            'name' => $this->product_set['name'],
            'filter' => json_encode($this->product_set['filter'])
        ];
        Log::debug($body);

        $resp = FbUtils::makeRequest(null, $endpoint, null, 'POST', $body, '', $fbApiToken->token);
        if ($resp['success']) {
            Log::debug("Create product set, catalog id: {$catalog->source_id}");
        } else {
            $message = "Create product set failed\r\n catalog name:{$catalog->name}\r\n bm id: {$this->bm_id}";
            Log::warning($message);
        }
    }

    public function tags(): array
    {
        return [
            "FB-Update-Create-ProductSet",
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Create product set failed: ' . $exception->getMessage());
        $message = "Create product set failed\r\n bm id: {$this->bm_id}\r\n catalog id: {$this->catalog_id}";
        Telegram::sendMessage($message);
    }
}
