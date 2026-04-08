<?php

namespace App\Jobs;

use App\Models\Network;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class TriggerNetworkFetchClicks implements ShouldQueue
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
        Log::info("--- Trigger Network Clicks: {$this->date_start} to {$this->date_stop}");

        // 检查这些 network id 是否在数据库中

        $networks = Network::query()
            ->where('active', true)
            ->whereIn('id', $this->network_ids)
            ->get(); // 获取完整的 Network 模型实例集合

        // 定义分割天数
        $splitAfterDays = 2;

        // 并发触发
        $jobs = collect(); // 创建一个空集合来存储所有任务

        foreach ($networks as $network) {
            $startDate = Carbon::parse($this->date_start);
            $endDate = Carbon::parse($this->date_stop);

            if ($network->system_type === 'Everflow') {
                while ($startDate->lte($endDate)) {
                    $nextDate = (clone $startDate)->addDays($splitAfterDays - 1);
                    if ($nextDate->gt($endDate)) {
                        $nextDate = $endDate;
                    }

                    $jobs->push(new NetworkFetchClicks($network->id, $startDate->toDateString(), $nextDate->toDateString()));

                    $startDate = (clone $nextDate)->addDay();
                    if ($startDate->gt($endDate)) {
                        break;
                    }
                }
            } else {
                $jobs->push(new NetworkFetchClicks($network->id, $this->date_start, $this->date_stop));
            }
        }

        Bus::batch($jobs)->allowFailures()->then(function () {
            Log::info(" --- all fetch network clicks jobs are done ---");
        })->catch(function (Batch $batch, Throwable $e) {
            Log::warning("fetch insights, some jobs failed, please check :");
            Log::warning($e->getMessage());
        })->onQueue('network')->dispatch();


    }

    public function tags(): array
    {
        return [
            'Trigger',
            'Network-Clicks',
            "{$this->date_start}",
            "{$this->date_stop}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('TriggerNetworkFetchClicks Job failed: ' . $exception->getMessage());
    }
}
