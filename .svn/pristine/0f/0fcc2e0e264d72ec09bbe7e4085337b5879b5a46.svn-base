<?php

namespace App\Jobs;

use App\Models\Card;
use App\Services\CardProviderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CardSetSingleTransLimit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $card;
    private $singleTransLimit;

    /**
     * Create a new job instance.
     */
    public function __construct($card_id, $singleTransLimit)
    {
        $this->card = Card::with('cardProvider')->findOrFail($card_id);
        $this->singleTransLimit = $singleTransLimit;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Set single transaction limit for card: {$this->card['name']}, new limit: {$this->singleTransLimit}");

        if (!$this->card->cardProvider) {
            Log::error("Card has no associated provider: {$this->card->id}");
            throw new \Exception('Card has no associated provider');
        }

        $cardProviderService = app(CardProviderService::class);

        try {
            $provider = $cardProviderService->getProviderByCard($this->card);
            $success = $provider->setPerTransactionLimit($this->card->source_id, $this->singleTransLimit);

            if (!$success) {
                throw new \Exception('Provider failed to set single transaction limit');
            }

            Log::info("Successfully set single transaction limit for card: {$this->card['name']}, limit: {$this->singleTransLimit}");

        } catch (\Exception $e) {
            Log::error("Failed to set single transaction limit for card: {$e->getMessage()}");
            throw $e;
        }
    }

    public function tags()
    {
        return [
            "Set-Single-Trans-Limit",
            "{$this->card['name']}",
            "{$this->card['id']}",
            "limit-{$this->singleTransLimit}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Set Single Transaction Limit Job failed: ' . $exception->getMessage());
    }
}