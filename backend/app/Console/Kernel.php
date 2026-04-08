<?php

namespace App\Console;

use App\Jobs\AutomationPipeline;
use App\Jobs\BatchUpdateFbItemStatus;
use App\Jobs\FraudDetectionScanJob;
use App\Jobs\GenProviderSpend;
use App\Jobs\GenCwPartnerSpend;
use App\Jobs\VccBalanceCheck;
use App\Models\CronJob;
use App\Models\FbAd;
use App\Models\FbAdAccount;
use App\Models\FbAdset;
use App\Models\FbCampaign;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->job(new AutomationPipeline(), 'network')->cron('*/20 * * * *');

        // VCC 余额监控 - 每10分钟检查一次
        $schedule->job(new VccBalanceCheck())->everyTenMinutes();

        // 防盗刷检测 - 每15分钟扫描一次
        $schedule->job(new FraudDetectionScanJob())->everyFifteenMinutes();

        // 每20分钟获取一下数据
        $schedule->call(function () {
            $currentDate = Carbon::now('UTC')->addHours(8)->toDateString();
            $startOfMonth = Carbon::now('UTC')->addHours(8)->startOfMonth()->toDateString();

            GenProviderSpend::dispatch($startOfMonth, $currentDate);
        })->cron('*/20 * * * *');

        // 每20分钟生成CW合作伙伴消耗数据（最近7天，从2025-10-30开始）
        $schedule->call(function () {
            $currentDate = Carbon::now('UTC')->addHours(8)->toDateString();
            $sevenDaysAgo = Carbon::now('UTC')->addHours(8)->subDays(7)->toDateString();

            // 确保开始时间不早于2025-10-30
            $minStartDate = '2025-10-30';
            if ($sevenDaysAgo < $minStartDate) {
                $sevenDaysAgo = $minStartDate;
            }

            GenCwPartnerSpend::dispatch($sevenDaysAgo, $currentDate);
        })->cron('*/20 * * * *');

        $schedule->call(function () {
            $response = Http::get('https://openexchangerates.org/api/latest.json', [
                'app_id' => env('OPEN_EXCHANGE_RATES_API_KEY')
            ]);

            if ($response->successful()) {
                $rates = $response->json()['rates'];

                foreach ($rates as $currency => $rate) {
                    Cache::put("exchange_rate_USD_{$currency}", $rate, now()->addHours(10));
                }
            }
        })->everySixHours();

        #TODO: 资源存在性检查
        $jobs = CronJob::query()->where('active', true)->get();
        Log::debug("checking cronjob");
        foreach ($jobs as $job) {
            $start_time = $job->start_time;
            $stop_time = $job->stop_time;
            $timezone = $job->timezone;
            $user_id = $job->user_id;
            if ($start_time) {
                $object_type = $job->object_type;
                $object_value = $job->object_value;
                if (in_array($object_type, ['Campaign Tag', 'Adset Tag', 'Campaign ID', 'Adset ID', 'Ad ID' ])) {
                    if ($object_type === 'Campaign ID') {
                        $clean_ids = $this->filterObjectValues($object_type, $object_value, $user_id);
                        $schedule->job(new BatchUpdateFbItemStatus($clean_ids, 'ACTIVE', 'campaign'))
                            ->dailyAt($start_time->format('H:i'))
                            ->timezone($timezone);
                    } else if ($object_type === 'Adset ID') {
                        $clean_ids = $this->filterObjectValues($object_type, $object_value, $user_id);
                        $schedule->job(new BatchUpdateFbItemStatus($clean_ids, 'ACTIVE', 'adset'))
                            ->dailyAt($start_time->format('H:i'))
                            ->timezone($timezone);
                    } else if ($object_type === 'Ad ID') {
                        $clean_ids = $this->filterObjectValues($object_type, $object_value, $user_id);
                        $schedule->job(new BatchUpdateFbItemStatus($clean_ids, 'ACTIVE', 'ad'))
                            ->dailyAt($start_time->format('H:i'))
                            ->timezone($timezone);
                    } else if ($object_type === 'Campaign Tag') {
                        $campaigns = FbCampaign::query()->whereHas('tags', function ($query) use ($object_value, $user_id) {
                                $query->where('tags.user_id', $user_id)->whereIn('name', $object_value);
                        })->pluck('source_id');
                        Log::debug('campaign , start_time: ' . $start_time);
                        Log::debug($campaigns);
                        $schedule->job(new BatchUpdateFbItemStatus($campaigns, 'ACTIVE', 'campaign'))
                            ->dailyAt($start_time->format('H:i'))
                            ->timezone($timezone);
                    } else if ($object_type === 'Adset Tag') {
                        $adsets = FbAdset::query()->whereHas('tags', function ($query) use ($object_value, $user_id) {
                            $query->where('tags.user_id', $user_id)->whereIn('name', $object_value);
                        })->pluck('source_id');
                        $schedule->job(new BatchUpdateFbItemStatus($adsets, 'ACTIVE', 'adset'))
                            ->dailyAt($start_time->format('H:i'))
                            ->timezone($timezone);
                    }
                }
            };
            if ($stop_time) {
                $object_type = $job->object_type;
                $object_value = $job->object_value;
                if (in_array($object_type, ['Campaign Tag', 'Adset Tag', 'Campaign ID', 'Adset ID', 'Ad ID' ])) {
                    if ($object_type === 'Campaign ID') {
                        $schedule->job(new BatchUpdateFbItemStatus($object_value, 'PAUSED', 'campaign'))
                            ->dailyAt($stop_time->format('H:i'))
                            ->timezone($timezone);
                    } else if ($object_type === 'Adset ID') {
                        $schedule->job(new BatchUpdateFbItemStatus($object_value, 'PAUSED', 'adset'))
                            ->dailyAt($stop_time->format('H:i'))
                            ->timezone($timezone);
                    } else if ($object_type === 'Ad ID') {
                        $schedule->job(new BatchUpdateFbItemStatus($object_value, 'PAUSED', 'ad'))
                            ->dailyAt($stop_time->format('H:i'))
                            ->timezone($timezone);
                    } else if ($object_type === 'Campaign Tag') {
                        $userId = $job->user_id;
                        $campaigns = FbCampaign::query()->whereHas('tags', function ($query) use ($object_value, $userId) {
                            $query->where('tags.user_id', $userId)->whereIn('name', $object_value);
                        })->pluck('source_id');
                        $schedule->job(new BatchUpdateFbItemStatus($campaigns, 'PAUSED', 'campaign'))
                            ->dailyAt($stop_time->format('H:i'))
                            ->timezone($timezone);
                    } else if ($object_type === 'Adset Tag') {
                        $userId = $job->user_id;
                        $adsets = FbAdset::query()->whereHas('tags', function ($query) use ($object_value, $userId) {
                            $query->where('tags.user_id', $userId)->whereIn('name', $object_value);
                        })->pluck('source_id');
                        $schedule->job(new BatchUpdateFbItemStatus($adsets, 'PAUSED', 'adset'))
                            ->dailyAt($stop_time->format('H:i'))
                            ->timezone($timezone);
                    }
                }
            };
        }
    }

    public function filterObjectValues($object_type, array $object_value, $user_id)
    {
        $model = null;
        $relationMethod = null;

        // 根据 object_type 决定使用哪个模型和关系
        switch ($object_type) {
            case 'Campaign ID':
                $model = FbCampaign::class;
                $relationMethod = 'fbAdAccount';
                break;
            case 'Adset ID':
                $model = FbAdset::class;
                $relationMethod = 'fbAdAccount';
                break;
            case 'Ad ID':
                $model = FbAd::class;
                $relationMethod = 'fbAdAccountV2';
                break;
            default:
                return []; // 如果类型不支持，返回空数组
        }

        $clean_ids = [];

        foreach ($object_value as $sourceId) {
            $record = $model::query->where('source_id', $sourceId)->first();

            if ($record) {
                $account = $record->$relationMethod;
                $admin = User::query()->firstWhere('name', 'admin');
                $admin_id = $admin->id;
                if ($account && ($user_id == $admin_id || $account->user_id == $user_id)) {
                    // 如果是管理员用户 user_id，则不需要过滤
                    $clean_ids[] = $sourceId;
                }
            }
        }

        return $clean_ids;
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
