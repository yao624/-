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

class CardFreeze implements ShouldQueue
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
        Log::info("freeze card: {$this->card['name']}");

        if (!$this->card->cardProvider) {
            Log::error("Card has no associated provider: {$this->card->id}");
            throw new \Exception('Card has no associated provider');
        }

        $cardProviderService = app(CardProviderService::class);

        try {
            $provider = $cardProviderService->getProviderByCard($this->card);
            $success = $provider->freezeCard($this->card->source_id);

            if (!$success) {
                throw new \Exception('Provider failed to freeze card');
            }

        } catch (\Exception $e) {
            Log::error("Failed to freeze card: {$e->getMessage()}");
            throw $e;
        }
    }

    public function tags()
    {
        return [
            "freeze-Card",
            "{$this->card['name']}",
            "{$this->card['id']}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Freeze Card Job failed: ' . $exception->getMessage());
    }
}
