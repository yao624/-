<?php

namespace App\Jobs;

use App\Models\FbAccount;
use App\Models\FbAdAccount;
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

class AutomationPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private mixed $date_start;
    private mixed $date_stop;

    /**
     * Create a new job instance.
     */
    public function __construct($date_start=null, $date_stop=null)
    {
        $this->date_start = $date_start;
        $this->date_stop = $date_stop;
        if ($this->date_start == null || $this->date_stop == null) {
            $today = Carbon::now('Etc/GMT+8');
            $this->date_start = $today->toDateString();
            $this->date_stop = $today->toDateString();
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("--- Automation pipeline started: {$this->date_start} to {$this->date_stop} ---");

        $active_networks = Network::query()->where('active', true)
            ->whereNot('is_subnetwork', true)->get(); // keitaro 的 network 这个标志位为 true
        Log::debug("=> 需要获取 {$active_networks->count()} 个联盟的数据");
        $pull_network_data_jobs = [];

        $carbonDate = Carbon::createFromFormat('Y-m-d', $this->date_start, 'Etc/GMT+8');
        $network_start_date = $carbonDate->copy()->subDays()->toDateString();
        $network_stop_date = $carbonDate->copy()->addDays()->toDateString();
        foreach ($active_networks as $network) {
            Log::debug(">>> ad pull data job for network: {$network->name}, {$network_start_date}, {$network_stop_date}");
            $pull_network_data_jobs[] = new NetworkFetchClicks($network->id, $network_start_date, $network_stop_date);
            $pull_network_data_jobs[] = new NetworkFetchConversions($network->id, $network_start_date, $network_stop_date);
        }

        $parent_date_start = $this->date_start;
        $parent_date_stop = $this->date_stop;

        if (empty($pull_network_data_jobs)) {
            $pull_network_data_jobs[] = new DummyJob();
        }

        Bus::batch($pull_network_data_jobs)->then(function (Batch $batch) use ($parent_date_stop, $parent_date_start) {
            Log::info("successfully pulled network data");

            // 获取广告账户id, 操作号的 token 是可用的，并且没有归档, auto_sync 打开的
//            $fb_ad_accounts = FbAdAccount::whereHas('fbAccounts', function ($query) {
//                $query->where('token_valid', true);}
//            )->where('is_archived', false)->where('auto_sync', true)->get();

            $fb_ad_accounts = FbAdAccount::where('is_archived', false)
                ->where('auto_sync', true)
                ->where(function ($query) {
                    $query->whereHas('fbAccounts', function ($subQuery) {
                        $subQuery->where('token_valid', true);
                    })->orWhereHas('apiTokens', function ($subQuery) {
                        $subQuery->where('active', true);
                    });
                })
                ->get();

            Log::debug("=> 需要获取 {$fb_ad_accounts->count()} 个广告账户");

            $pull_fb_data_job_list = [];
            $pull_fb_data_job_list[] = new DummyJob(); // 增加一个空 job，保证 then 里面的任务会执行

            // 同步广告账户的状态以及 insights 的 job。目前没有加入 api 账户
            foreach ($fb_ad_accounts as $fb_ad_account) {
                Log::debug("fb ad account: {$fb_ad_account->id}");
                // 获取每个账户的时区，再指定date_start, date_stop
                $timezone_name = $fb_ad_account['timezone_name'];
                $date = Carbon::now($timezone_name);
                $date_start = $date->copy()->subDays(1)->format('Y-m-d');
                $date_stop = $date->format('Y-m-d');

                $fb_account = $fb_ad_account->fbAccounts()->where('token_valid', true)->first();
                if ($fb_account) {
                    Log::debug(">>> ad pull facebook data job for ad account: {$fb_ad_account->name} via {$fb_account->name}, {$date_start} to {$date_stop}");
                    $pull_fb_data_job_list[] = new FacebookSyncAdAccount($fb_ad_account->id, $date_start, $date_stop, $fb_account->id, true);
//                    $pull_fb_data_job_list[] = new FacebookSyncResources($fb_account->id, $fb_ad_account->id, $date_start, $date_stop, true);
                } else {
                    $pull_fb_data_job_list[] = new FacebookSyncAdAccount($fb_ad_account->id, $date_start, $date_stop, null, true,[
                        'field' => 'updated_time',
                        'value' => Carbon::now()->subDays(1)->timestamp,
                        'operator' => 'GREATER_THAN',
                    ]);
                }
            }

            // 同步个号拥有的资源状态
            $valid_fb_acc = FbAccount::query()->where('token_valid', true)->get();
            foreach ($valid_fb_acc as $fb_acc) {
                $pull_fb_data_job_list[] = new FacebookSyncResources($fb_acc->id);
            }

            $check_rule_date_start = $parent_date_start;
            $check_rule_date_stop = $parent_date_stop;

            Bus::batch($pull_fb_data_job_list)->then(function (Batch $batch) use ($check_rule_date_stop, $check_rule_date_start) {
                Log::info("successfully pull facebook data");
            })->catch(function (Batch $batch, Throwable $e){
                Log::warning("some jobs failed when pulling facebook data");
            })->finally(function (Batch $batch) use ($check_rule_date_stop, $check_rule_date_start) {
                Log::info('--- finished pull fb data ----');
                AutomationCheckRule::dispatch($check_rule_date_start, $check_rule_date_stop)->onQueue('facebook');
            })->onQueue('facebook')->allowFailures()->dispatch();

        })->catch(function (Batch $batch, Throwable $e) {
            Log::warning("some jobs failed when pulling network data");
        })->finally(function (Batch $batch) {
            Log::info("--- Automation pipeline finished ---");
        })->onQueue('network')->allowFailures()->dispatch();
    }

    public function tags()
    {
        return [
            "Automation",
            "{$this->date_start}",
            "{$this->date_stop}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Automation Pipeline failed: ' . $exception->getMessage());
    }
}
