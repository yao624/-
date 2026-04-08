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

class CardOneShotPerTrans implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $card;
    private $per_trans;
    private $source_id;

    /**
     * Create a new job instance.
     */
    public function __construct($source_id, $per_trans=1200)
    {
        $this->source_id = $source_id;
        $this->per_trans = $per_trans;
        $this->card = Card::with('cardProvider')->where('source_id', $this->source_id)->first();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->card) {
            Log::error("Card not found with source_id: {$this->source_id}");
            throw new \Exception('Card not found');
        }

        Log::info("set card per trans limit, card: {$this->card['name']}, {$this->per_trans}");

        if (!$this->card->cardProvider) {
            Log::error("Card has no associated provider: {$this->card->id}");
            throw new \Exception('Card has no associated provider');
        }

        $cardProviderService = app(CardProviderService::class);

        try {
            $provider = $cardProviderService->getProviderByCard($this->card);
            $success = $provider->setPerTransactionLimit($this->card->source_id, $this->per_trans);

            if (!$success) {
                throw new \Exception('Provider failed to set per transaction limit');
            }

        } catch (\Exception $e) {
            Log::error("Failed to set per transaction limit: {$e->getMessage()}");
            throw $e;
        }
    }

    public function tags()
    {
        return [
            "Card-OneShot-PerTrans",
            "{$this->per_trans}",
            "{$this->card['name']}",
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('One Shot Total Limit Job failed: ' . $exception->getMessage());
    }
}
