<?php

namespace App\Jobs;

use App\Enums\BidStrategy;
use App\Models\FbAdAccount;
use App\Models\FbAdset;
use App\Models\FbCampaign;
use App\Utils\CurrencyUtils;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FacebookUpdateBidStrategy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private FbAdAccount $fbAdAccount;
    private FbAdset $fbAdset;
    private FbCampaign $fbCampaign;
    private float $bid_amount;
    private string $obj_type;
    private string $bid_strategy;
    private string $id;
    /**
     * Create a new job instance.
     */
    public function __construct(FbAdAccount $fbAdAccount, string $bid_strategy, string $obj_type, string $id, float $value=0.0)
    {
        $this->fbAdAccount = $fbAdAccount;
        $this->obj_type = $obj_type;
        $this->id = $id;
        $this->bid_amount = $value;
        $this->bid_strategy = $bid_strategy;
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

        $body = [];

        if ($this->bid_amount > 0) {
            $convert_amount = CurrencyUtils::convertAndFormat($this->bid_amount,'USD', $this->fbAdAccount->currency );
            $currency_offset = CurrencyUtils::$currencyConfig[$this->fbAdAccount->currency]['offset'];
            $convert_amount = $currency_offset * $convert_amount;
        }

        if ($this->obj_type === 'campaign') {
            $this->fbCampaign = FbCampaign::query()->where('id', $this->id)->firstOrFail();
            $endpoint = "https://graph.facebook.com/{$version}/{$this->fbCampaign->source_id}";
            if ($this->bid_strategy === BidStrategy::HighestVolume->value) {
                $body['bid_strategy'] = BidStrategy::HighestVolume->value;
                $body['pacing_type'] = [BidStrategy::PacingTypeStandard->value];
            } elseif (in_array($this->bid_strategy, [BidStrategy::BidCap->value, BidStrategy::CostPerResultGoal->value])) {
                $body['bid_strategy'] = $this->bid_strategy;
                $adset_source_ids = $this->fbCampaign->fbAdsets()
                    ->whereNotIn('configured_status', ['ARCHIVED', 'DELETED'])
                    ->pluck('source_id')->toArray();
                $body['adset_bid_amounts'] = array_fill_keys($adset_source_ids, $convert_amount);
            }
        } else if ($this->obj_type === 'adset') {
            $this->fbAdset = FbAdset::query()->where('id', $this->id)->firstOrFail();
            $endpoint = "https://graph.facebook.com/{$version}/{$this->fbAdset->source_id}";
            $body['bid_strategy'] = $this->bid_strategy;
            if (in_array($this->bid_strategy, [BidStrategy::BidCap->value, BidStrategy::CostPerResultGoal->value])) {
                $body['bid_amount'] = $convert_amount;
            }
        }

        Log::debug("url: {$endpoint}");
        Log::debug(json_encode($body));

        $resp = FbUtils::makeRequest(null, $endpoint, null, 'POST', $body, '', $fbApiToken->token);
        if ($resp['success']) {
            Log::debug("Update bid strategy success, acc:{$this->fbAdAccount->source_id}, type:{$this->obj_type}, id:{$this->id} , value: {$this->bid_amount}");
        } else {
            $message = "Update bid strategy failed\r\n acc:{$this->fbAdAccount->source_id}\r\n type:{$this->obj_type}\r\n id: {$this->id}\r\n value: {$this->bid_amount}";
            Log::warning($message);
            Telegram::sendMessage($message);
        }
    }

    public function tags(): array
    {
        return [
            "FB-Update-Bid-Strategy",
            "{$this->fbAdAccount->source_id}",
            "type-{$this->obj_type}-id-{$this->id}",
            "{$this->bid_strategy}",
            "{$this->bid_amount}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Update bid strategy failed: ' . $exception->getMessage());
        $message = "Update bid strategy failed\r\n acc:{$this->fbAdAccount->source_id}\r\n type:{$this->obj_type}\r\n id: {$this->id}\r\n value: {$this->bid_amount}";
        Telegram::sendMessage($message);
    }
}
