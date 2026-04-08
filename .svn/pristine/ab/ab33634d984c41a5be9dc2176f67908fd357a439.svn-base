<?php

namespace App\Jobs;

use App\Models\Rule;
use App\Utils\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutomationExecuteRule implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $rule;
    private $metrics;
    /**
     * Create a new job instance.
     */
    public function __construct(Rule $rule, $metrics)
    {
        $this->rule = $rule;
        $this->metrics = $metrics;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $scope = $this->rule['scope'];
        if ($scope === 'ad_account') {
            $this->rule->execute($this->metrics, $this->metrics['ad_account_name'], $this->metrics['ad_account_name'], $this->metrics['ad_account_id'], $scope, $this->metrics['ad_account_id']);
        } elseif ($scope === 'campaign') {
            $this->rule->execute($this->metrics, $this->metrics['ad_account_name'], $this->metrics['campaign_name'], $this->metrics['campaign_id'], $scope, $this->metrics['ad_account_id']);
        } elseif ($scope === 'adset') {
            $this->rule->execute($this->metrics, $this->metrics['ad_account_name'], $this->metrics['adset_name'], $this->metrics['adset_id'], $scope, $this->metrics['ad_account_id']);
        } elseif ($scope === 'ad') {
            $this->rule->execute($this->metrics, $this->metrics['ad_account_name'], $this->metrics['ad_name'], $this->metrics['ad_id'], $scope, $this->metrics['ad_account_id']);
        } elseif ($scope === 'camp_tag') {
            $this->rule->execute($this->metrics, $this->metrics['ad_account_name'], $this->metrics['campaign_name'], $this->metrics['campaign_id'], $scope, $this->metrics['ad_account_id']);
        } elseif ($scope === 'adset_tag') {
            $this->rule->execute($this->metrics, $this->metrics['ad_account_name'], $this->metrics['adset_name'], $this->metrics['adset_id'], $scope, $this->metrics['ad_account_id']);
        } elseif ($scope === 'ad_tag') {
            $this->rule->execute($this->metrics, $this->metrics['ad_account_name'], $this->metrics['ad_name'], $this->metrics['ad_id'], $scope, $this->metrics['ad_account_id']);
        }
    }

    public function tags()
    {
        return [
            "Automation",
            "{$this->rule['id']}",
            "{$this->rule['name']}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Automation Execute Rule failed: ' . $exception->getMessage());
        $msg = "🚒🚒🚒Rule execute failed: {$this->rule['name']}, metrics ad account: {$this->metrics['ad_account_id']}";
        Telegram::sendMessage($msg);
    }
}
