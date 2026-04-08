<?php

namespace App\Jobs;

use App\Models\FbBm;
use App\Models\FbCatalog;
use App\Models\FbCatalogProduct;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FacebookCatalogUploadVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ?string $bm_id;
    private string $catalog_id;
    private string $retailer_id;
    private string $video_url;
    /**
     * Create a new job instance.
     */
    public function __construct($bm_id, $catalog_id, $retailer_id, $video_url)
    {
        $this->bm_id = $bm_id;
        $this->catalog_id = $catalog_id;
        $this->retailer_id = $retailer_id;
        $this->video_url = $video_url;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $catalog = FbCatalog::query()->where('id', $this->catalog_id)->firstOrFail();

        // 获取合适的FbApiToken
        $fbApiToken = $this->getFbApiToken($catalog);

        if (!$fbApiToken) {
            throw new \Exception("Unable to find suitable FbApiToken for catalog: {$this->catalog_id}");
        }

        Log::debug("Using token: {$fbApiToken->token}");

        $body = [
            'item_type' => 'PRODUCT_ITEM',
            'requests' => json_encode([
                [
                    'method' => 'UPDATE',
                    'data' => [
                        'id' => $this->retailer_id,
                        'video' => [
                            [
                                'url' => $this->video_url
                            ]
                        ]
                    ]
                ]
            ]),
        ];
        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/{$catalog->source_id}/items_batch";

        $resp = FbUtils::makeRequest(null, $endpoint, null, 'POST', $body, '', $fbApiToken->token);
        if ($resp['success']) {
            Log::info("catalog: {$catalog->source_id} product : {$this->retailer_id} add video: {$this->video_url}");
            $product = FbCatalogProduct::query()->firstWhere('retailer_id', $this->retailer_id);
            if ($product) {
                $product->video_url = $this->video_url;
                $product->video_handler = $resp['video_handler'] ?? '';
                $product->save();
            }
        } else {
            $bmIdInfo = $this->bm_id ? "bm:{$this->bm_id}" : "bm:auto-detected";
            $message = "Failed to upload product video:\r\n {$bmIdInfo}\r\ncatalog: {$this->catalog_id}\r\nproduct: {$this->retailer_id}";
            Log::warning($message);
            Telegram::sendMessage($message);
        }
    }

    /**
     * 获取合适的FbApiToken
     * 如果提供了bm_id，直接根据bm_id查找
     * 如果没有提供bm_id，通过catalog->bm的关系链查找
     */
    private function getFbApiToken(FbCatalog $catalog)
    {
        // 如果提供了bm_id，直接使用原有逻辑
        if ($this->bm_id) {
            $bm = FbBm::query()->firstWhere('id', $this->bm_id);
            if ($bm) {
                return $bm->fbApiTokens()
                    ->where('token_type', 1)
                    ->where('active', true)
                    ->first();
            }
        }

        // 如果没有提供bm_id，通过关系链查找
        // Catalog -> BM -> ApiToken
        $bms = $catalog->fbBms;

        foreach ($bms as $bm) {
            $token = $bm->fbApiTokens()
                ->where('token_type', 1)
                ->where('active', true)
                ->first();

            if ($token) {
                Log::debug("Found suitable token from BM: {$bm->source_id} for catalog: {$this->catalog_id}");
                return $token;
            }
        }

        Log::warning("Unable to find suitable FbApiToken for catalog: {$this->catalog_id}");
        return null;
    }

    public function tags(): array
    {
        $bmIdTag = $this->bm_id ? $this->bm_id : 'auto-detected';
        return [
            'FB-Catalog-Upload-Video',
            "{$bmIdTag}",
            "{$this->catalog_id}",
            "{$this->retailer_id}",
            "{$this->video_url}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('update product video failed: ' . $exception->getMessage());
        $bmIdInfo = $this->bm_id ? "bm id: {$this->bm_id}" : "bm id: auto-detected";
        $message = "Upload product video failed\r\n catalog id:{$this->catalog_id}\r\n retailer_id: {$this->retailer_id}\r\n {$bmIdInfo}";
        Telegram::sendMessage($message);
    }
}
