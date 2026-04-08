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

class TriggerFacebookFetchCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $fbAdAccountIds;
    private $date_start;
    private $date_stop;
    private $next;

    /**
     * Create a new job instance.
     */
    public function __construct($fbAdAccountIds, $date_start, $date_stop, $next)
    {
        $this->fbAdAccountIds = $fbAdAccountIds;
        $this->date_start = $date_start;
        $this->date_stop = $date_stop;
        $this->next = $next;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("fetch facebook campaign data");
        $jobs = collect($this->fbAdAccountIds)->map(function ($id) {
            return new FacebookFetchCampaign($id, $this->date_start, $this->date_stop, null, $this->next);
        });
        Bus::batch($jobs)->allowFailures()
            ->then(function (Batch $batch) {
                Log::info(" --- all jobs are done");
            })
            ->catch(function (Batch $batch, Throwable $e) {
                // Batch失败时执行...
                Log::warning("jobs failed, please check :");
                Log::warning($e->getMessage());
            })
            ->onQueue('facebook')->dispatch();
    }

    public function tags(): array
    {
        return [
            'Trigger',
            'FB',
            'FetchCamp',
            "{$this->date_start}-{$this->date_stop}: {$this->next}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Job failed: ' . $exception->getMessage());
    }
}
