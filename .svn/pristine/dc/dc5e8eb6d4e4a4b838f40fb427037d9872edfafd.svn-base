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

class CardOneShotTotalLimit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $card;
    private $total_limit;
    private $source_id;

    /**
     * Create a new job instance.
     */
    public function __construct($source_id, $total_limit=2)
    {
        $this->source_id = $source_id;
        $this->total_limit = $total_limit;
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

        Log::info("set card total limit, card: {$this->card['name']}, {$this->total_limit}");

        // 关闭的卡无法设置
        if ($this->card['status'] == 'CLOSED') {
            return;
        }

        if (!$this->card->cardProvider) {
            Log::error("Card has no associated provider: {$this->card->id}");
            throw new \Exception('Card has no associated provider');
        }

        $cardProviderService = app(CardProviderService::class);

        try {
            $provider = $cardProviderService->getProviderByCard($this->card);
            $success = $provider->setTotalLimit($this->card->source_id, $this->total_limit);

            if (!$success) {
                throw new \Exception('Provider failed to set total limit');
            }

            // 更新本地记录
            $this->card->update(['limits' => $this->total_limit]);

        } catch (\Exception $e) {
            Log::error("Failed to set total limit: {$e->getMessage()}");
            throw $e;
        }
    }

    public function tags()
    {
        return [
            "Card-OneShot-Total",
            "{$this->total_limit}",
            "{$this->card['name']}",
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('One Shot Total Limit Job failed: ' . $exception->getMessage());
    }
}
