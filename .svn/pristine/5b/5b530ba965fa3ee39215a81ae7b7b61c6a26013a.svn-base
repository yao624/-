<?php

namespace App\Jobs;

use App\Models\FbBm;
use App\Models\FbCatalogProduct;
use App\Models\FbCatalogProductSet;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FacebookUpdateProduct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private  ?string $bm_id;
    private  string $id;
    private  string $name;
    private  string $desc;
    private  string $url;
    private  string $image_url;
    private  string $price;

    /**
     * Create a new job instance.
     */
    public function __construct($bm_id, $id, $name, $desc, $url, $image_url, $price)
    {
        $this->bm_id = $bm_id;
        $this->id = $id;
        $this->name = $name;
        $this->desc = $desc;
        $this->url = $url;
        $this->image_url = $image_url;
        $this->price = $price;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $product = FbCatalogProduct::query()->where('id', $this->id)->firstOrFail();

        // 获取合适的FbApiToken
        $fbApiToken = $this->getFbApiToken($product);

        if (!$fbApiToken) {
            throw new \Exception("Unable to find suitable FbApiToken for product: {$this->id}");
        }

        Log::debug("Using token: {$fbApiToken->token}");

        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/{$product->source_id}";

        $body = [
            "name" => $this->name,
            "description" => $this->desc,
            "url" => $this->url,
            "image_url" => $this->image_url,
            "price" => $this->price,
        ];
        Log::debug($body);

        $resp = FbUtils::makeRequest(null, $endpoint, null, 'POST', $body, '', $fbApiToken->token);
        if ($resp['success']) {
            Log::debug("Update product success, product id: {$product->source_id}");
        } else {
            $message = "Update product failed\r\n product name:{$product->name}\r\n product id: {$product->source_id}";
            Log::warning($message);
        }
    }

    /**
     * 获取合适的FbApiToken
     * 如果提供了bm_id，直接根据bm_id查找
     * 如果没有提供bm_id，通过product->catalog->bm的关系链查找
     */
    private function getFbApiToken(FbCatalogProduct $product)
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
        // Product -> Catalog -> BM -> ApiToken
        $catalog = $product->catalog;
        if (!$catalog) {
            Log::warning("Product {$this->id} has no associated catalog");
            return null;
        }

        // 查找与catalog关联的BM，并获取合适的token
        $bms = $catalog->fbBms;

        foreach ($bms as $bm) {
            $token = $bm->fbApiTokens()
                ->where('token_type', 1)
                ->where('active', true)
                ->first();

            if ($token) {
                Log::debug("Found suitable token from BM: {$bm->source_id} for product: {$this->id}");
                return $token;
            }
        }

        Log::warning("Unable to find suitable FbApiToken for product: {$this->id}");
        return null;
    }

    public function tags(): array
    {
        $bmIdTag = $this->bm_id ? $this->bm_id : 'auto-detected';
        return [
            "FB-Update-Product-set",
            "{$this->id}",
            "{$this->name}",
            "{$bmIdTag}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Update product failed: ' . $exception->getMessage());
        $bmIdInfo = $this->bm_id ? "bm id: {$this->bm_id}" : "bm id: auto-detected";
        $message = "Update product failed\r\n product id:{$this->id}\r\n {$bmIdInfo}";
        Telegram::sendMessage($message);
    }
}
