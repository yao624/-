<?php

namespace App\Jobs;

use App\Models\FbAdAccount;
use App\Models\FbBm;
use App\Models\FbCatalog;
use App\Models\FbPixel;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FacebookSharePixel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private FbBm $business_manager;
    private FbAdAccount $ad_account;
    private FbCatalog $catalog;
    private FbPixel $pixel;
    private string $share_type;
    private string $action;
    /**
     * Create a new job instance.
     */
    public function __construct($pixel_id, $business_id, $action, $share_type, $ad_account_id, $catalog_id)
    {
        Log::debug("$pixel_id, $business_id, $ad_account_id, $share_type");
        $this->business_manager = FbBm::query()->where('id', $business_id)->firstOrFail();
        if ($ad_account_id) {
            $this->ad_account = FbAdAccount::query()->where('id', $ad_account_id)->firstOrFail();
        }
        if ($catalog_id) {
            $this->catalog = FbCatalog::query()->where('id', $catalog_id)->firstOrFail();
        }

        $this->pixel = FbPixel::query()->where('id', $pixel_id)->firstOrFail();
        $this->share_type = $share_type;
        $this->action = $action;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // https://developers.facebook.com/docs/marketing-api/reference/ads-pixel/shared_accounts/

        // 根据 BM id，查询 token type 为 1 的 token
        $fbApiToken = $this->business_manager->fbApiTokens()->where('token_type', 1)
            ->where('active', true)->firstOrFail();
        Log::debug($fbApiToken->token);

        $version = FbUtils::$API_Version;
        $source_id = '';
        if ($this->share_type === 'ad_account') {
            $body = [
                'account_id' => $this->ad_account->source_id,
                'business' => $this->business_manager->source_id
            ];
            $endpoint = "https://graph.facebook.com/{$version}/{$this->pixel->pixel}/shared_accounts";
            $source_id = $this->ad_account->source_id;
        } elseif ($this->share_type === 'catalog') {
            $body = [
                'external_event_sources' => json_encode(["{$this->pixel->pixel}"]),
            ];
            $endpoint = "https://graph.facebook.com/{$version}/{$this->catalog->source_id}/external_event_sources";
            $source_id = $this->catalog->source_id;
        }
        if ($this->action === 'share') {
            $method = 'POST';
        } elseif ($this->action === 'unshare') {
            $method = 'DELETE';
        }

        $resp = FbUtils::makeRequest(null, $endpoint, null, $method, $body, '', $fbApiToken->token);
        if ($resp['success']) {
            Log::debug("share BM({$this->business_manager->source_id}) Pixel({$this->pixel->pixel}) to {$this->share_type}({$source_id}) success");
        } else {
            $message = "share BM({$this->business_manager->source_id}) Pixel({$this->pixel->pixel}) to {$this->share_type}({$source_id}) failed";
            Log::warning($message);
        }
    }

    public function tags(): array
    {
        return [
            "FB-Pixel-{$this->share_type}",
            "{$this->action}-{$this->pixel->pixel}",
            "{$this->business_manager->source_id}",
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Share pixel failed: ' . $exception->getMessage());
        Telegram::sendMessage("share pixel failed");
    }

}
