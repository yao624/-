<?php

namespace App\Jobs;

use App\Models\FbAd;
use App\Models\FbAdset;
use App\Models\FbCampaign;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FacebookDeleteAdObject implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $source_id;
    private string $object_type;
    /**
     * Create a new job instance.
     */
    public function __construct(string $source_id, string $object_type)
    {
        $this->source_id = $source_id;
        $this->object_type = $object_type;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->object_type === 'ad') {
            $adAccount = FbAd::query()->firstWhere('source_id', $this->source_id)->fbAdAccountV2;
        } elseif ($this->object_type === 'campaign') {
            $adAccount = FbCampaign::query()->firstWhere('source_id', $this->source_id)->fbAdAccount;
        } elseif ($this->object_type === 'adset') {
            $adAccount = FbAdset::query()->firstWhere('source_id', $this->source_id)->fbAdAccount;
        }
        if (!$adAccount) {
            Log::warning("can not find ad account for {$this->object_type} {$this->source_id}");
            return;
        }
        $fbApiToken = $adAccount->apiTokens()->where('active', true)->firstOrFail();

        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/{$this->source_id}";
        $resp = FbUtils::makeRequest(null, $endpoint, null, 'DELETE', null, '', $fbApiToken->token);
        if ($resp['success']) {
            Log::debug("Delete {$this->object_type} {$this->source_id} success");
        } else {
            $message = "Delete {$this->object_type} {$this->source_id} failed";
            Log::warning($message);
        }
    }

    public function tags(): array
    {
        return [
            "FB-Delete-{$this->object_type}",
            "{$this->source_id}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error("Delete {$this->object_type} {$this->source_id} failed. " . $exception->getMessage());
        $message = "Delete {$this->object_type} {$this->source_id} failed";
        Telegram::sendMessage($message);
    }
}
