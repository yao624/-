<?php

namespace App\Jobs;

use App\Models\Card;
use App\Services\CardProviderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CardSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $source_id;
    private  $sync_cvv;

    /**
     * Create a new job instance.
     */
    public function __construct($source_id, $sync_cvv=false)
    {
        $this->source_id = $source_id;
        $this->sync_cvv = $sync_cvv;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("CardSync job started for source_id: {$this->source_id}");
        $card = Card::with('cardProvider')->where('source_id', $this->source_id)->first();

        if (!$card) {
            Log::error("Card not found with source_id: {$this->source_id}");
            throw new \Exception('Card not found');
        }

        if (!$card->cardProvider) {
            Log::error("Card has no associated provider: {$card->id}");
            throw new \Exception('Card has no associated provider');
        }

        $cardProviderService = app(CardProviderService::class);

        try {
            $provider = $cardProviderService->getProviderByCard($card);
            $result = $provider->syncCard($this->source_id, $this->sync_cvv);

            if ($this->sync_cvv) {
                Card::updateOrCreate(
                    ['source_id' => $this->source_id],
                    array_merge($result, ['card_provider_id' => $card->card_provider_id])
                );
            } else {
                Card::where('source_id', $this->source_id)->update([
                    'name' => $result['name'],
                    'status' => $result['status'],
                    'balance' => $result['balance'] ?? 0,
                    'single_transaction_limit' => $result['single_transaction_limit'] ?? null,
                    'currency' => $result['currency']
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Failed to sync card: {$e->getMessage()}");
            throw $e;
        }
    }

    public function tags()
    {
        return [
            "Card-Sync",
            "{$this->source_id}",
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Sync Card Job failed: ' . $exception->getMessage());
    }
}
