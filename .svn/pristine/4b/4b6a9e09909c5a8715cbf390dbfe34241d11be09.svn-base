<?php

namespace App\Jobs;

use App\Models\FbBm;
use App\Models\FbPage;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FacebookBmClaimPage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private FbBm $fb_bm;
    private FbPage $page;
    /**
     * Create a new job instance.
     */
    public function __construct($bm_id, $page_id)
    {
        $this->fb_bm = FbBm::query()->where('id', $bm_id)->firstOrFail();
        $this->page = FbPage::query()->where('id', $page_id)->firstOrFail();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // https://developers.facebook.com/docs/marketing-api/business-asset-management/guides/pages
        $fbApiToken = $this->fb_bm->fbApiTokens()->where('token_type', 1)
            ->where('active', true)->firstOrFail();
        Log::debug($fbApiToken->token);
        $body = [
            'page_id' => $this->page->source_id,
            'permitted_tasks' => [
                'MANAGE', 'CREATE_CONTENT', 'MODERATE', 'ADVERTISE', 'ANALYZE'
            ]
        ];
        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/{$this->fb_bm->source_id}/client_pages";

        $resp = FbUtils::makeRequest(null, $endpoint, null, 'POST', $body, '', $fbApiToken->token);
        if ($resp['success']) {
            Log::debug("BM({$this->fb_bm->source_id}) request Page({$this->page->source_id}) access success");
        } else {
            $message = "BM({$this->fb_bm->source_id}) request Page({$this->page->source_id}) access failed";
            Log::warning($message);
        }
    }

    public function tags(): array
    {
        $bm_source_id = $this->fb_bm->source_id ?? '';
        $page_source_id = $this->page->source_id ?? '';
        return [
            'FB-BM-Claim-Page',
            "{$bm_source_id}",
            "{$page_source_id}",
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('BM request page access failed: ' . $exception->getMessage());
        Telegram::sendMessage("BM request page access failed");
    }
}
