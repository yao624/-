<?php

namespace App\Jobs;

use App\Models\FbAdAccount;
use App\Models\FbAdset;
use App\Utils\CurrencyUtils;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FacebookUpdateBidAmount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private FbAdAccount $fbAdAccount;
    private FbAdset $fbAdset;
    private float $bid_amount;
    /**
     * Create a new job instance.
     */
    public function __construct(FbAdAccount $fbAdAccount, string $id, float $value)
    {
        $this->fbAdAccount = $fbAdAccount;
        $this->fbAdset = FbAdset::query()->where('id', $id)->firstOrFail();
        $this->bid_amount = $value;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 根据 BM id，查询 token type 为 1 的 token
        $fbApiToken = $this->fbAdAccount->apiTokens()->where('active', true)->firstOrFail();
        Log::debug($fbApiToken->token);

        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/{$this->fbAdset->source_id}";

        $convert_amount = CurrencyUtils::convertAndFormat($this->bid_amount,'USD', $this->fbAdAccount->currency );
        $currency_offset = CurrencyUtils::$currencyConfig[$this->fbAdAccount->currency]['offset'];
        $convert_amount = $currency_offset * $convert_amount;

        $body = [
            'bid_amount' => $convert_amount,
        ];
        Log::debug("converted amount: {$convert_amount}");

        $resp = FbUtils::makeRequest(null, $endpoint, null, 'POST', $body, '', $fbApiToken->token);
        if ($resp['success']) {
            Log::debug("Update bid amount success, adset id: {$this->fbAdset->source_id}, value: {$this->bid_amount}");
        } else {
            $message = "Update bid amount failed\r\n acc:{$this->fbAdAccount->source_id}\r\n adset: {$this->fbAdset->source_id}\r\n value: {$convert_amount}";
            Log::warning($message);
        }
    }

    public function tags(): array
    {
        return [
            "FB-Update-Bid-Amount",
            "{$this->fbAdAccount->source_id}",
            "adset-{$this->fbAdset->source_id}",
            "{$this->bid_amount}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Update adset bid amount failed: ' . $exception->getMessage());
        Telegram::sendMessage("Update adset {$this->fbAdset->source_id} bid amount failed");
    }
}
