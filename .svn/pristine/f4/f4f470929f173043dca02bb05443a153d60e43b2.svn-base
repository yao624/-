<?php

namespace App\Jobs;

use App\Models\FbAdAccount;
use App\Models\Tag;
use App\Models\User;
use App\Utils\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class GenProviderSpend implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private mixed $dateStart;
    private mixed $dateStop;
    public $timeout = 3600;
    /**
     * Create a new job instance.
     */
    public function __construct($dateStart, $dateStop)
    {
        $this->dateStart = $dateStart;
        $this->dateStop = $dateStop;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $appName = config('app.name');
//        return;
        if (!in_array($appName, ['Gemini-kmh', 'Laravel'])) {
            return;
        }

        $adminUserId = User::query()->first()->id; // 假设 admin 的 user_id 为 1
        $providers = [
//            'provider_star' => 'P:Star',
//            'provider_aileway' => 'P:Aileway',
//            'provider_fuxi' => 'P:Fuxi',
            'provider_mc_cn2' => 'P:MC-CN2',
            'provider_atom_cl' => 'P:ATOM-CL',
            'provider_ysc_cn2' => 'P:YSC-CN2',
            'provider_ysc_bm' => 'P:YSC-BM',
            'provider_ysc_cl' => 'P:YSC-CL',
            'provider_ysc_tmi' => 'P:YSC-TMI',
        ];

        $startDate = Carbon::parse($this->dateStart);
        $endDate = Carbon::parse($this->dateStop);

        foreach ($providers as $key => $tagName) {
            $tag = Tag::where('name', $tagName)->where('user_id', $adminUserId)->first();

            if ($tag) {
                Log::debug("tag: {$tag->name}");
                $accounts = FbAdAccount::whereHas('tags', function ($query) use ($tag) {
                    $query->where('tags.name', $tag->name);
                })->get();
                Log::debug("accounts cnt: " . $accounts->count());

                // 遍历日期范围内的每一天
                for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                    $dailySpending = 0;

                    foreach ($accounts as $account) {
                        // 使用 sum() 方法计算特定日期的消费总和
                        $dailySpending += $account->insights()
                            ->whereDate('date_start', $date) // 特定日期的消费
                            ->sum('spend');
                    }

                    $cacheKey = "provider_spendings:{$key}:{$date->toDateString()}";
                    Redis::set($cacheKey, $dailySpending);
                    Log::debug("set {$cacheKey}, value: {$dailySpending}");
                }
            }
        }
    }
    public function tags(): array
    {
        return [
            'GenProviderSpending',
            "{$this->dateStart}:{$this->dateStop}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('GenProviderSpending Job failed: ' . $exception->getMessage());
        Telegram::sendMessage("GenProviderSpending Job failed: ");
    }
}
