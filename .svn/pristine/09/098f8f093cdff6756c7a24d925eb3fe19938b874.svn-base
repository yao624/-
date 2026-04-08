<?php

namespace App\Jobs;

use App\Utils\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BatchUpdateFbItemStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

    private $item_source_ids;
    private $status;
    private $item_type;
    public function __construct($item_source_ids, $status, $item_type)
    {
        $this->item_source_ids = $item_source_ids;
        $this->status = $status;
        $this->item_type = $item_type;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->item_source_ids as $object_id) {
            //$message = "定时任务: $this->item_type, $this->status, $object_id";
            //Telegram::sendMessage($message);
            //Log::debug($message);
            ActionUpdateFbAdItemStauts::dispatch($object_id, $this->status, $this->item_type)->onQueue('facebook');
        }
    }

    public function tags()
    {
        return [
            "Batch-Update-{$this->item_type}",
            "{$this->status}"
        ];
    }
}
