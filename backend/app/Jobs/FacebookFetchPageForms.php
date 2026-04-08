<?php

namespace App\Jobs;

use App\Models\FbAccount;
use App\Models\FbPage;
use App\Models\FbPageForm;
use App\Utils\FbUtils;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FacebookFetchPageForms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    private $page_source_id;
    /**
     * Create a new job instance.
     */
    public function __construct($page_source_id)
    {
        $this->page_source_id = $page_source_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("--- start fetch page forms {$this->page_source_id} ---");
        $page = FbPage::query()->where('source_id', $this->page_source_id)
            ->whereNotNull('tokens')->firstOrFail();

//        if (!$page['access_token']) {
//            Log::warning("page access token is empty");
//            return;
//        }

        $query = [
            'fields' => 'id,locale,name,status,created_time,thank_you_page,questions,privacy_policy_url,legal_content,follow_up_action_text,follow_up_action_url,leads_count,page{id,name}',
        ];
        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/{$this->page_source_id}/leadgen_forms";

        $tokens = $page->tokens;

        $token_object = $tokens[0];
        $owner_type = $token_object['owner_type'];
        $owner_id = $token_object['owner_id'];
        $token = $token_object['token'];
        if ($owner_type === 'fb') {
            $fb_account = FbAccount::query()->where('id', $owner_id)->firstOrFail();
            $resp = FbUtils::makeRequest($fb_account, $endpoint, $query, 'GET', '', 'fetch-page-forms', $token);
        } elseif ($owner_type === 'bm') {
            $resp = FbUtils::makeRequest(null, $endpoint, $query, 'GET', '', 'fetch-page-forms', $token);
        }

        Log::debug($resp->toJson());

        if ($resp['success']) {
            $form_data = collect($resp['data'] ?? []);
            $form_data->each(function ($form) {
//                Log::debug($form);
                FbPageForm::query()->updateOrCreate(
                    [
                        'source_id' => $form['id']
                    ],
                    [
                        'source_id' => $form['id'],
                        'locale' => $form['locale'],
                        'name' => $form['name'],
                        'status' => $form['status'],
                        'created_time' => $form['created_time'],
                        'thank_you_page' => $form['thank_you_page'] ?? [],
                        'privacy_policy_url' => $form['privacy_policy_url'] ?? '',
                        'questions' => $form['questions'] ?? [],
                        'legal_content' => $form['legal_content'] ?? [],
                        'follow_up_action_url' => $form['follow_up_action_url'],
                        'leads_count' => $form['leads_count'],
                        'page_source_id' => $form['page']['id'],
                        'page_name' => $form['page']['name'],
                    ]
                );
            });
        }
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Fetch Page Forms Job failed: ' . $exception->getMessage());
    }

    public function tags(): array
    {
        return [
            'FB-Page-Forms',
            "{$this->page_source_id}"
        ];
    }
}
