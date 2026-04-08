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

class CardSetBalance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $card;
    private $balance;

    /**
     * Create a new job instance.
     */
    public function __construct($card_id, $balance)
    {
        $this->card = Card::with('cardProvider')->findOrFail($card_id);
        $this->balance = $balance;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Set balance for card: {$this->card['name']}, new balance: {$this->balance}");

        if (!$this->card->cardProvider) {
            Log::error("Card has no associated provider: {$this->card->id}");
            throw new \Exception('Card has no associated provider');
        }

        $cardProviderService = app(CardProviderService::class);

        try {
            $provider = $cardProviderService->getProviderByCard($this->card);
            $success = $provider->setBalance($this->card->source_id, $this->balance);

            if (!$success) {
                throw new \Exception('Provider failed to set card balance');
            }

            Log::info("Successfully set balance for card: {$this->card['name']}, balance: {$this->balance}");

        } catch (\Exception $e) {
            Log::error("Failed to set balance for card: {$e->getMessage()}");
            throw $e;
        }
    }

    public function tags()
    {
        return [
            "Set-Balance-Card",
            "{$this->card['name']}",
            "{$this->card['id']}",
            "balance-{$this->balance}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Set Balance Card Job failed: ' . $exception->getMessage());
    }
}