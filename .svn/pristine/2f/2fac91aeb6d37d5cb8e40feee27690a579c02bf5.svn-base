<?php

namespace App\Jobs;

use App\Models\Card;
use App\Models\CardTransaction;
use App\Services\CardProviderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CardSyncAllTrans implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $timeout = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $token = CardUtils::get_token();
        $base_url = CardUtils::$base_url;
        $endpoint = "{$base_url}/api/v1/issuing/transactions";

        $page_num = 0;
        $params = [
            'page_num' => $page_num,
            'page_size' => 200
        ];

        $has_more = true;
        while ($has_more) {
            $resp = Http::withToken($token)->timeout(120)->connectTimeout(120)->get($endpoint, $params);
            if ($resp->successful()) {
                $has_more = $resp->json('has_more');

                $items = collect($resp->json('items'));
                $source_ids = $items->pluck('card_id');
                $cards = Card::query()->whereIn('source_id', $source_ids)->get();

                foreach ($items as $item) {
                    Log::debug($item);
                    $transaction_id = $item['transaction_id'];
                    $status = $item['status'];
                    $transaction_amount = -floatval($item['transaction_amount']);
                    $transaction_date = Carbon::parse($item['transaction_date']);
                    $transaction_type = $item['transaction_type'];
                    $merchant_name = $item['merchant']['name'];
                    $custom_1 = $item['auth_code'];
                    $card = $cards->firstWhere('source_id', $item['card_id']);
                    if (!$card) {
                        Log::warning("card: {$item['card_id']} not in db");
                        continue;
                    }
                    $card_id = $card['id'];
                    $posted_date = $item['posted_date'] ? Carbon::parse($item['posted_date']) : null;
                    $failure_reason = $item['failure_reason'] ?? "";

                    CardTransaction::query()->updateOrCreate(
                        [
                            'source_id' => $transaction_id
                        ],
                        [
                            'status' => $status,
                            'transaction_amount' => $transaction_amount,
                            'transaction_date' => $transaction_date,
                            'transaction_type' => $transaction_type,
                            'merchant_name' => $merchant_name,
                            'custom_1' => $custom_1,
                            'card_id' => $card_id,
                            'posted_date' => $posted_date,
                            'failure_reason' => $failure_reason,
                        ]
                    );
                }
                $params['page_num'] = $params['page_num'] + 1;

            } else {
                Log::error("failed to sync all card trans, status: {$resp->status()}, body: {$resp->body()}");
                throw new \Exception('failed to sync all card trans');
            }
        }
    }

    public function tags()
    {
        return [
            "Sync-All-CardTransactions",
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Sync All Card Transactions Job failed: ' . $exception->getMessage());
    }
}
