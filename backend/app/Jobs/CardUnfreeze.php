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

class CardUnfreeze implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private  $card;

    /**
     * Create a new job instance.
     */
    public function __construct($card_id)
    {
        $this->card = Card::with('cardProvider')->findOrFail($card_id);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Unfreeze card: {$this->card['name']}");

        if (!$this->card->cardProvider) {
            Log::error("Card has no associated provider: {$this->card->id}");
            throw new \Exception('Card has no associated provider');
        }

        $cardProviderService = app(CardProviderService::class);

        try {
            $provider = $cardProviderService->getProviderByCard($this->card);
            $success = $provider->unfreezeCard($this->card->source_id);

            if (!$success) {
                throw new \Exception('Provider failed to unfreeze card');
            }

        } catch (\Exception $e) {
            Log::error("Failed to unfreeze card: {$e->getMessage()}");
            throw $e;
        }
    }

    public function tags()
    {
        return [
            "Unfreeze-Card",
            "{$this->card['name']}",
            "{$this->card['id']}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Unfreeze Card Job failed: ' . $exception->getMessage());
    }
}
