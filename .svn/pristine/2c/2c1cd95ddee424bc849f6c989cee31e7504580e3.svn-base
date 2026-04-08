<?php

namespace App\Jobs;

use App\Models\Card;
use App\Models\CardProvider;
use App\Services\CardProviderService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CardCreate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $card_name;
    private $balance;
    private $card_provider_id;

    /**
     * Create a new job instance.
     */
    public function __construct($card_name, $balance, $card_provider_id)
    {
        $this->card_name = $card_name;
        $this->balance = $balance;
        $this->card_provider_id = $card_provider_id;
    }

        /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 必须指定provider
        if (!$this->card_provider_id) {
            Log::error("Card provider ID is required");
            throw new Exception("Card provider ID is required");
        }

        $cardProvider = CardProvider::find($this->card_provider_id);
        if (!$cardProvider || !$cardProvider->active) {
            Log::error("Card provider not found or inactive: {$this->card_provider_id}");
            throw new Exception("Card provider not found or inactive");
        }

        $cardProviderService = app(CardProviderService::class);
        $provider = $cardProviderService->getProviderByModel($cardProvider);

        try {
            $result = $provider->createCard($this->card_name, $this->balance);

            Card::create([
                'name' => $this->card_name,
                'source_id' => $result['card_id'],
                'status' => $result['status'],
                'balance' => $result['balance'],
                'number' => $result['number'],
                'cvv' => $result['cvv'],
                'expiration' => $result['expiration'],
                'card_provider_id' => $this->card_provider_id
            ]);

        } catch (Exception $e) {
            Log::error("Failed to create card: {$e->getMessage()}");
            throw $e;
        }
    }

    public function tags()
    {
        return [
          "Create-Card",
          "{$this->balance}",
          "{$this->card_name}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Create Card Job failed: ' . $exception->getMessage());
    }
}
