<?php

namespace App\Jobs;

use App\Models\FbApiToken;
use App\Utils\Telegram;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class TriggerFacebookFetchApiResource implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $ids;
    /**
     * Create a new job instance.
     */
    public function __construct(array $ids)
    {
        $this->ids = $ids;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $tokenIds = FbApiToken::query()->where('active', true)->whereIn('id', $this->ids)
            ->pluck('id')->toArray();

        $fetch_api_resource_jobs = [];
        foreach ($tokenIds as $token) {
//            FacebookSyncApiResource::dispatch($token['id']);
            $fetch_api_resource_jobs[] = new FacebookSyncApiResource($token);
        }

        Bus::batch($fetch_api_resource_jobs)->finally(function (Batch $batch) use ($tokenIds) {
            Log::debug("fetch page forms");
            $all_page_source_id = FbApiToken::with('fbPages')->whereIn('id', $tokenIds)
                ->where('active', true)->where('token_type', 1)
                ->get()->pluck('fbPages.*.source_id')->flatten()->toArray();
//            $fetch_page_forms_jobs = [];
//            foreach ($all_page_source_id as $page_source_id) {
//                $fetch_page_forms_jobs[] = new FacebookFetchPageForms($page_source_id);
//            }
//            Bus::batch($fetch_page_forms_jobs)->onQueue('facebook-page-form')->name('Fetch Page Form')
//                ->allowFailures()->dispatch();
        })->onQueue('frontend')->allowFailures()->dispatch();
    }

    public function tags()
    {
        return [
            'Trigger-Sync-Apitoken-resource'
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Trigger sync api token failed: ' . $exception->getMessage());
        $msg = 'failed to sync api token';
        Telegram::sendMessage($msg);
    }
}
