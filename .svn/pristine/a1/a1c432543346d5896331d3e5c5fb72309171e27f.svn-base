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

class CardSyncTrans implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $source_id;

    /**
     * Create a new job instance.
     */
    public function __construct($source_id)
    {
        $this->source_id = $source_id;
        $this->card = Card::query()->firstWhere('source_id', $source_id);
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
            'page_size' => 200,
            'card_id' => $this->source_id
        ];

        $has_more = true;
        while ($has_more) {
            $resp = Http::withToken($token)->get($endpoint, $params);
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
                    $card_id = $cards->firstWhere('source_id', $item['card_id'])->id;
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
            "Sync-Card-Trans",
            "{$this->card['name']}",
            "{$this->source_id}",
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Sync Card Trans Job failed: ' . $exception->getMessage());
    }
}
