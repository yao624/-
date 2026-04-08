<?php

namespace App\Jobs;

use App\Models\FbBm;
use App\Models\FbCatalogProductSet;
use App\Utils\CurrencyUtils;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FacebookUpdateProductSet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $bm_id;
    private string $product_set_id;
    private string $name;
    private array $filter;
    /**
     * Create a new job instance.
     */
    public function __construct(string $bm_id, string $product_set_id, string $name, array $filter)
    {
        $this->bm_id = $bm_id;
        $this->product_set_id = $product_set_id;
        $this->name = $name;
        $this->filter = $filter;
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

        $product_set = FbCatalogProductSet::query()->where('id', $this->product_set_id)->firstOrFail();

        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/{$product_set->source_id}";

        $body = [
            'name' => $this->name,
            'filter' => json_encode($this->filter)
        ];
        Log::debug($body);

        $resp = FbUtils::makeRequest(null, $endpoint, null, 'POST', $body, '', $fbApiToken->token);
        if ($resp['success']) {
            Log::debug("Update product set success, adset id: {$product_set->name}");
        } else {
            $message = "Update product set failed\r\n product set name:{$product_set->name}\r\n product set id: {$product_set->source_id}";
            Log::warning($message);
        }
    }

    public function tags(): array
    {
        return [
            "FB-Update-Product-set",
            "{$this->bm_id}",
            "{$this->product_set_id}",
            "{$this->name}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Update product set failed: ' . $exception->getMessage());
        $message = "Update product set failed\r\n product set id:{$this->product_set_id}\r\n bm id: {$this->bm_id}";
        Telegram::sendMessage($message);
    }
}
