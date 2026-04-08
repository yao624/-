<?php

namespace App\Console\Commands;

use App\Models\FbAdAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ArchiveRestrictedAdAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:archive-restricted-ad-account';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = 0;
        Log::info("-- start archived ad account when account is disabled ---");
        FbAdAccount::query()->each(function ($fb_ad_account) use (&$count) {
            if ($fb_ad_account->account_status_code == 2) {
                $fb_ad_account->is_archived = true;
                $fb_ad_account->save();
                $count = $count+1;
            }
        });
        Log::info("-- end of the operation, archived: {$count} ad accounts ---");

    }
}
