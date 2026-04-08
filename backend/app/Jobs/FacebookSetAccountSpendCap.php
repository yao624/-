<?php

namespace App\Jobs;

use App\Models\FbAdAccount;
use App\Utils\CurrencyUtils;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FacebookSetAccountSpendCap implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $acc_source_id;
    private string $cap_type;
    private string $cap_value;
    /**
     * Create a new job instance.
     */
    public function __construct(string $acc_source_id, string $cap_type, float $cap_value=0.0)
    {
        $this->acc_source_id = $acc_source_id;
        $this->cap_type = $cap_type;
        $this->cap_value = $cap_value;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fbAdAccount = FbAdAccount::query()->where('source_id', $this->acc_source_id)->firstOrFail();
        $fbApiToken = $fbAdAccount->apiTokens()->where('active', true)->firstOrFail();

        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/act_{$this->acc_source_id}";

        if ($this->cap_type === 'reset') {
            $body = [
                'spend_cap_action' => 'reset'
            ];
        } elseif ($this->cap_type === 'remove') {
            $body = [
                'spend_cap_action' => 'delete'
            ];
        } elseif ($this->cap_type === 'amount') {
            $convert_amount = CurrencyUtils::convertAndFormat($this->cap_value,'USD', $fbAdAccount->currency );
            $body = [
                'spend_cap' => $convert_amount
            ];
        }

        $resp = FbUtils::makeRequest(null, $endpoint, null, 'POST', $body, '', $fbApiToken->token);
        if ($resp['success']) {
            Log::debug("Update acc cap success, acc:{$this->acc_source_id}, type: {$this->cap_type}, value: {$this->cap_value}");
        } else {
            $message = "Update acc cap failed, acc:{$this->acc_source_id}, type: {$this->cap_type}, value: {$this->cap_value}";
            Log::warning($message);
        }
    }

    public function tags(): array
    {
        return [
            "FB-Update-Cap",
            "{$this->acc_source_id}",
            "{$this->cap_type}",
            "{$this->cap_value}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Update acc cap failed: ' . $exception->getMessage());
        Telegram::sendMessage("Update acc cap failed, acc:{$this->acc_source_id}, type: {$this->cap_type}, value: {$this->cap_value}");
    }
}
