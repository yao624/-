<?php

namespace App\Jobs;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class TriggerFacebookFetchInsights implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $fbAdAccountIds;

    /**
     * Create a new job instance.
     */
    public function __construct($fbAdAccountIds)
    {
        $this->fbAdAccountIds = $fbAdAccountIds;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("fetch facebook insights");
        $jobs = collect($this->fbAdAccountIds)->map(function ($id) {
            return new FacebookFetchAdAccountInsights($id);
        });
        Bus::batch($jobs)->allowFailures()
            ->then(function (Batch $batch) {
                Log::info(" --- all jobs are done");
            })
            ->catch(function (Batch $batch, Throwable $e) {
                // Batch失败时执行...
                Log::warning("fetch insights, some jobs failed, please check :");
                Log::warning($e->getMessage());
            })
            ->onQueue('facebook')->dispatch();
    }

    public function tags(): array
    {
        return [
            'Trigger',
            'FB',
            'FetchInsights'
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('TriggerFacebookFetchInsights Job failed: ' . $exception->getMessage());
    }
}
