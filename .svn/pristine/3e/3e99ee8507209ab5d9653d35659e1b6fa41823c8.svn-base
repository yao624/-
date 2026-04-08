<?php

namespace App\Jobs;

use App\Models\Tracker;
use App\Utils\KeitaroUtils;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TrackerFetchData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id;
    protected $name;
    protected $date_start;
    protected $date_stop;
    /**
     * Create a new job instance.
     */
    public function __construct($id, $name, $date_start, $date_stop)
    {
        $this->id = $id;
        $this->name = $name;
        $this->date_start = $date_start;
        $this->date_stop = $date_stop;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $tracker = Tracker::query()->where('id', $this->id)->first();

        // 确保 tracker 不为 null
        if ($tracker) {
            KeitaroUtils::FetchCampaign($tracker);
            KeitaroUtils::FetchOfferClicks($tracker, $this->date_start, $this->date_stop);
        } else {
            Log::warning("Tracker with id {$this->id} not found.");
            // 或者抛出一个异常
            // throw new \Exception("Tracker not found.");
        }
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Tracker FetchData Job failed: ' . $exception->getMessage());
    }

    public function tags(): array
    {
        return [
            'Tracker-Fetch-Data',
            "{$this->name}",
            "{$this->date_start}",
            "{$this->date_stop}"
        ];
    }
}
