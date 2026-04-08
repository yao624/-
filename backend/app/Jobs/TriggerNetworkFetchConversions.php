<?php

namespace App\Jobs;

use App\Models\Network;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class TriggerNetworkFetchConversions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $network_ids;
    private $date_start;
    private $date_stop;

    /**
     * Create a new job instance.
     */
    public function __construct($network_ids, $date_start, $date_stop)
    {
        $this->network_ids = $network_ids;
        $this->date_start = $date_start;
        $this->date_stop = $date_stop;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("--- Trigger Network Conversions: {$this->date_start} to {$this->date_stop} --- ");

        // 检查这些 network id 是否在数据库中

        $existingID = Network::query()->where('active', true)->whereIn('id', $this->network_ids)->pluck('id');

        // 并发触发
        $jobs = $existingID->map(function ($networkID) {
            return new NetworkFetchConversions($networkID,  $this->date_start, $this->date_stop);
        });
        Bus::batch($jobs)->allowFailures()->then(function (){
            Log::info(" --- all fetch network conv jobs are done ---");
        })->catch(function (Batch $batch, Throwable $e) {
            Log::warning("fetch conversions, some jobs failed, please check :");
            Log::warning($e->getMessage());
        })->onQueue('network')->dispatch();
    }

    public function tags(): array
    {
        return [
            'Trigger',
            'Network-Conv',
            "{$this->date_start}",
            "{$this->date_stop}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('TriggerNetworkFetchConversions Job failed: ' . $exception->getMessage());
    }
}
