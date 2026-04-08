<?php

namespace App\Jobs;

use App\Models\FbCampaign;
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

class GenCwPartnerSpend implements ShouldQueue
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
        if (!in_array($appName, ['Laravel'])) {
            return;
        }

        // CW 合作伙伴的投手标签和对应的用户名
        $cwPartners = [
            'cw_rt' => ['tag_name' => 'CW-RT', 'username' => 'admin'],
            'cw_hq' => ['tag_name' => 'CW-HQ', 'username' => 'haoquan.wang'],
            'cw_wh' => ['tag_name' => 'CW-WH', 'username' => 'wuhan'],
            'cw_ht' => ['tag_name' => 'CW-HT', 'username' => 'hutao'],
        ];

        $startDate = Carbon::parse($this->dateStart);
        $endDate = Carbon::parse($this->dateStop);

        // 确保开始时间不早于2025年10月30日
        $minStartDate = Carbon::parse('2025-10-30');
        if ($startDate->lt($minStartDate)) {
            $startDate = $minStartDate;
        }

        foreach ($cwPartners as $key => $partnerInfo) {
            $tagName = $partnerInfo['tag_name'];
            $username = $partnerInfo['username'];

            // 根据用户名查找用户ID
            $user = User::where('name', $username)->first();
            if (!$user) {
                Log::warning("CW Partner user not found: {$username}");
                continue;
            }

            $tag = Tag::where('name', $tagName)->where('user_id', $user->id)->first();

            if ($tag) {
                Log::debug("CW Partner tag: {$tag->name}");

                // 获取带有该标签的所有Campaign，预加载广告账户关系
                $campaigns = FbCampaign::whereHas('tags', function ($query) use ($tag) {
                    $query->where('tags.name', $tag->name);
                })->with('fbAdAccount')->get();

                Log::debug("CW campaigns count: " . $campaigns->count());

                // 遍历日期范围内的每一天
                for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                    $dailySpend = 0;
                    $dailyOfferClicks = 0;
                    $dailyRevenue = 0;
                    $dailyOfferCpc = 0;

                    foreach ($campaigns as $campaign) {
                        // 计算消费 - 从 FbCampaignInsight
                        $dailySpend += $campaign->insights()
                            ->whereDate('date_start', $date->toDateString())
                            ->sum('spend');

                        // 每个campaign使用自己广告账户的时区
                        $timezone = $campaign->fbAdAccount->timezone_name ?? 'UTC';
                        $startDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date->toDateString(), $timezone)->startOfDay()->setTimezone('UTC');
                        $endDateInTimeZone = Carbon::createFromFormat('Y-m-d', $date->toDateString(), $timezone)->endOfDay()->setTimezone('UTC');

                        // 计算 offer clicks - 从 Click model
                        $offerClicksCount = $campaign->offerClicks()
                            ->whereBetween('click_datetime', [$startDateInTimeZone, $endDateInTimeZone])
                            ->count();
                        $dailyOfferClicks += $offerClicksCount;

                        // 计算 revenue - 从 Conversion model (price > 0)
                        $revenue = $campaign->offerConversions()
                            ->whereBetween('conversion_datetime', [$startDateInTimeZone, $endDateInTimeZone])
                            ->where('price', '>', 0)
                            ->sum('price');
                        $dailyRevenue += $revenue;
                    }

                    // 计算 offer CPC
                    $dailyOfferCpc = $dailyOfferClicks > 0 ? round($dailySpend / $dailyOfferClicks, 2) : 0;

                    // 存储到缓存
                    $dateString = $date->toDateString();
                    Redis::set("cw_partner_spend:{$key}:{$dateString}", $dailySpend);
                    Redis::set("cw_partner_offer_clicks:{$key}:{$dateString}", $dailyOfferClicks);
                    Redis::set("cw_partner_revenue:{$key}:{$dateString}", $dailyRevenue);
                    Redis::set("cw_partner_offer_cpc:{$key}:{$dateString}", $dailyOfferCpc);

                    Log::debug("CW Partner {$key} on {$dateString}: spend={$dailySpend}, clicks={$dailyOfferClicks}, revenue={$dailyRevenue}, cpc={$dailyOfferCpc}");
                }
            } else {
                Log::warning("CW Partner tag not found: {$tagName}");
            }
        }
    }

    public function tags(): array
    {
        return [
            'GenCwPartnerSpend',
            "{$this->dateStart}:{$this->dateStop}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('GenCwPartnerSpend Job failed: ' . $exception->getMessage());
        Telegram::sendMessage("GenCwPartnerSpend Job failed: " . $exception->getMessage());
    }
}
