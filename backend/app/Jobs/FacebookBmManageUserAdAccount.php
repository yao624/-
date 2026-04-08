<?php

namespace App\Jobs;

use App\Models\FbAdAccount;
use App\Models\FbBm;
use App\Models\FbBusinessUser;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FacebookBmManageUserAdAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $action;
    private FbBm $bm;
    private FbBusinessUser $bm_user;
    private FbAdAccount $ad_acc;
    private $role;
    /**
     * Create a new job instance.
     */
    public function __construct(string $action, string $bm_id, string $bm_user_id, string $ad_acc_id, string $role)
    {
        $this->bm = FbBm::query()->firstWhere('id', $bm_id);
        $this->bm_user = FbBusinessUser::query()->firstWhere('id', $bm_user_id);
        $this->ad_acc = FbAdAccount::query()->firstWhere('id', $ad_acc_id);
        $this->action = $action;
        $this->role = $role;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //https://developers.facebook.com/docs/marketing-api/business-asset-management/guides/ad-accounts/

        $fbApiToken = $this->bm->fbApiTokens()->where('token_type', 1)
            ->where('active', true)->firstOrFail();

        $tasks = FbUtils::getBmAdAccountTasksByName($this->role);
        if ($this->action === 'add') {
            $body = [
                'user' => $this->bm_user->source_id,
                'tasks' => $tasks
            ];
            $version = FbUtils::$API_Version;
            $endpoint = "https://graph.facebook.com/{$version}/act_{$this->ad_acc->source_id}/assigned_users";

            $resp = FbUtils::makeRequest(null, $endpoint, null, 'POST', $body, '', $fbApiToken->token);
            if ($resp['success']) {
                Log::debug("BM({$this->bm->source_id}) assign user({$this->bm_user->name}) to ad account({$this->ad_acc->source_id}) success");
            } else {
                $message = "BM({$this->bm->source_id}) assign user({$this->bm_user->name}) to ad account({$this->ad_acc->source_id}) failed";
                Log::warning($message);
                Telegram::sendMessage($message);
            }
        } else if($this->action === 'delete') {
            $body = [
                'user' => $this->bm_user->source_id,
            ];
            $version = FbUtils::$API_Version;
            $endpoint = "https://graph.facebook.com/{$version}/act_{$this->ad_acc->source_id}/assigned_users";

            $resp = FbUtils::makeRequest(null, $endpoint, null, 'DELETE', $body, '', $fbApiToken->token);
            if ($resp['success']) {
                Log::debug("BM({$this->bm->source_id}) remove user({$this->bm_user->name}) access ad account({$this->ad_acc->source_id}) success");
            } else {
                $message = "BM({$this->bm->source_id}) remove user({$this->bm_user->name}) access ad account({$this->ad_acc->source_id}) failed";
                Log::warning($message);
                Telegram::sendMessage($message);
            }
        }

    }

    public function tags(): array
    {
        $bm_source_id = $this->fb_bm->source_id ?? '';
        $user = $this->bm_user->name ?? '';
        $acc = $this->ad_acc->source_id ?? '';

        return [
            'FB-BM-MGT-USER-ACC',
            "{$bm_source_id}",
            "{$user}",
            "{$acc}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('update bm user ad account access failed: ' . $exception->getMessage());
        Telegram::sendMessage("update bm user ad account access failed");
    }
}
