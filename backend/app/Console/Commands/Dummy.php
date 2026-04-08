<?php

namespace App\Console\Commands;

use App\Enums\OperatorType;
use App\Jobs\AutomationCheckRule;
use App\Jobs\AutomationPipeline;
use App\Jobs\FacebookCreateAdsetV2;
use App\Jobs\FacebookCreateAdV2;
use App\Jobs\FacebookCreateCampaignV2;
use App\Jobs\FacebookFetchAd;
use App\Jobs\FacebookFetchAdset;
use App\Jobs\FacebookFetchAdsetV2;
use App\Jobs\FacebookFetchAdV2;
use App\Jobs\FacebookFetchCampaign;
use App\Jobs\FacebookFetchCampaignV2;
use App\Jobs\FacebookFetchPageForms;
use App\Jobs\FacebookFetchPagePost;
use App\Jobs\FacebookFetchPageToken;
use App\Jobs\FacebookSyncAdAccount;
use App\Jobs\GenProviderSpend;
use App\Jobs\GenCwPartnerSpend;
use App\Jobs\CardSyncAll;
use App\Jobs\CardSync;
use App\Jobs\CardSyncTransactions;
use App\Models\Click;
use App\Models\Conversion;
use App\Models\Material;
use App\Models\Network;
use App\Models\Rule;
use App\Models\User;
use App\Models\Card;
use App\Models\CardProvider;
use App\Models\FbAdAccount;
use App\Services\CardProviderService;
use App\Utils\Telegram;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use function Symfony\Component\Translation\t;

class Dummy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dummy {action} {params?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform actions like sending notifications or deleting data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::now('Etc/GMT+8');
//        AutomationPipeline::dispatch($today->toDateString(), $today->toDateString());
//        AutomationPipeline::dispatch()->onQueue('facebook');
//        AutomationCheckRule::dispatch('2024-02-15', '2024-02-15')->onQueue('facebook');
//        AutomationCheckRule::dispatch()->onQueue('facebook');
//        AutomationCheckRule::dispatch('2024-05-03', '2024-05-03')->onQueue('facebook');
//        Telegram::sendMessage("test ok");

        $action = $this->argument('action');
        $params = $this->argument('params');

        switch ($action) {
            case 'test-tg':
                Telegram::sendMessage("test ok");
                break;
            case 'delete-network-data-by-name':
                $this->deleteNetworkDataByName($params);
                break;
            case 'check-rule':
                $this->checkRule($params);
                break;
            case 'test-pipeline':
                $this->testPipeline();
                break;
            case 'test':
                $this->test();
                break;
            case 'page-forms':
                $this->page_forms($params);
                break;
            case 'create-campaign':
                $this->create_campaign();
                break;
            case 'create-adset':
                $this->create_asdset();
                break;
            case 'create-ad':
                $this->create_ad();
                break;
            case 'fetch-post':
                $this->fetch_post();
                break;
            case 'fetch-page-token':
                $this->fetch_page_token($params);
                break;
            case 'pull-campaign':
                $this->pull_campaign($params);
                break;
            case 'pull-adset':
                $this->pull_adset($params);
                break;
            case 'pull-ad':
                $this->pull_ad($params);
                break;
            case 'sync-ad-account':
                $this->sync_ad_account($params);
                break;
            case 'assign-rule-to-admin':
                $this->assign_rule_to_admin();
                break;
            case 'gen-provider-spending':
                $this->gen_provider_spending($params);
                break;
            case 'gen-cw-partner-spending':
                $this->genCwPartnerSpending($params);
                break;
            case 'update-material-types':
                $this->updateMaterialTypes();
                break;
            case 'sync-cards-all':
                $this->syncCardsAll($params);
                break;
            case 'sync-card':
                $this->syncCard($params);
                break;
            case 'sync-trans':
                $this->syncTrans($params);
                break;
            case 'auto-bind-card':
                $this->autoBindCard();
                break;
            case 'lock-unused-card':
                $this->lockUnusedCard($params);
                break;
            case 'spend-diff':
                $this->spendDiff();
                break;
            case 'spend-diff-processed':
                $this->spendDiffProcessed();
                break;
            case 'fix-mint-data':
                $this->fixMintData();
                break;
            case 'fix-clickstack-data':
                $this->fixClickstackData();
                break;
            case 'fix-bw-data':
                $this->fixBwData();
                break;
            case 'fix-fb-acc-auto-sync':
                $this->fixFbAccAutoSync();
                break;
            case 'fraud-scan':
                $this->fraudScan();
                break;
            case 'fraud-scan-ad-acc':
                $this->fraudScanAdAccount($params);
                break;
            case 'fraud-scan-ad':
                $this->fraudScanAd($params);
                break;
            case 'gen-provider-spend':
                $this->genProviderSpend($params);
                break;
            default:
                $this->error('Invalid action provided');
                break;
        }
    }

    private function deleteNetworkDataByName(bool|array|string|null $params)
    {
        // 实现根据名称删除网络数据的逻辑
        if (empty($params)) {
            Log::warning('No parameters provided for deletion');
            return;
        }

        DB::transaction(function () use ($params) {
            // 先根据名称查找所有相关的 network ids
            $networkIds = Network::whereIn('name', $params)->pluck('id');

            if ($networkIds->isNotEmpty()) {
                // 使用whereIn批量删除 Click 和 Conversion 数据，并记录删除的行数
                $clicksDeleted = Click::whereIn('network_id', $networkIds)->delete();
                $conversionsDeleted = Conversion::whereIn('network_id', $networkIds)->delete();

                $msg = "Deleted {$clicksDeleted} clicks and {$conversionsDeleted} conversions for networks: " . implode(', ', $params);
                echo $msg;
                Log::info($msg);
            }
        });
    }

    private function checkRule($params)
    {
        $check_rule_date_start = $params[0];
        $check_rule_date_stop = $params[0];
        Log::debug("check rule manual: {$check_rule_date_start} to {$check_rule_date_stop}");
        AutomationCheckRule::dispatch($check_rule_date_start, $check_rule_date_stop)->onQueue('facebook');
    }

    private function testPipeline()
    {
        AutomationPipeline::dispatch()->onQueue('facebook');
    }

    private function test()
    {
//        FacebookFetchAdset::dispatch('01jag6zmvjx5sc9vxtxv7bjxyf', null, null, null, true, true)->onQueue('facebook');
//        FacebookFetchAd::dispatch('01jag6zmvjx5sc9vxtxv7bjxyf')->onQueue('facebook');
//        FacebookFetchCampaign::dispatch('01jag6zmvjx5sc9vxtxv7bjxyf', null, null, null, false, true)->onQueue('facebook');

        $check_rule_date_start = '2025-01-21';
        $check_rule_date_stop = '2025-01-21';
        AutomationCheckRule::dispatch($check_rule_date_start, $check_rule_date_stop)->onQueue('facebook');

    }

    private function page_forms($params)
    {
        $page_source_id = $params[0];
        FacebookFetchPageForms::dispatch($page_source_id)->onQueue('facebook-page-form');
    }

    private function create_campaign()
    {
        FacebookCreateCampaignV2::dispatch('01jbyzf4m8qm7qmp793cps2v6p', OperatorType::BMUser->value,
            '01jbz0p0g8tnekdycef04gfp0t', '01jc4nkqgwemr8xckx5yx0k6v7',
            [
                'pixel_id' => '01j4a1k039v6hnthhym0681aad',
                'material_id' => '01jc2738x0cpefb2bm67znap4e',
                'page_id' => '01jbnx8pybnz70f6g8zb3w177c',
                'link_id' => '01jc2yyazw3drqcx4g2cmr5rbs',
                'copyWriting_id' => '01jc2z179ff6ntbs0hpvk9132q',
                'form_id' => '01jbq6x17c7gjsgyzh18p5ykte',
            ]);
    }

    private function create_asdset()
    {
        FacebookCreateAdsetV2::dispatch('01jbyzf4m8qm7qmp793cps2v6p', OperatorType::BMUser->value,
            '01jbz0p0g8tnekdycef04gfp0t', '120211341274300656', '01jc3nmcgbsqt3qv0vfxndmbz0', [
                'pixel_id' => '01j4a1k039v6hnthhym0681aad',
                'material_id' => '01jc2738x0cpefb2bm67znap4e',
                'page_id' => '01jbnx8pybnz70f6g8zb3w177c',
                'link_id' => '01jc2yyazw3drqcx4g2cmr5rbs',
                'copyWriting_id' => '01jc2z179ff6ntbs0hpvk9132q',
                'form_id' => '01jbq6x17c7gjsgyzh18p5ykte',
            ]);
    }

    private function create_ad()
    {
        FacebookCreateAdV2::dispatch('01jbyzf4m8qm7qmp793cps2v6p', OperatorType::BMUser->value,
            '01jbz0p0g8tnekdycef04gfp0t', '120211341274300656', '120211341306470656', '01jc3nmcgbsqt3qv0vfxndmbz0',
            [
                'pixel_id' => '01j4a1k039v6hnthhym0681aad',
                'material_id' => '01jc2738x0cpefb2bm67znap4e',
                'page_id' => '01jbnx8pybnz70f6g8zb3w177c',
                'link_id' => '01jc2yyazw3drqcx4g2cmr5rbs',
                'copyWriting_id' => '01jc2z179ff6ntbs0hpvk9132q',
                'form_id' => '01jbq6x17c7gjsgyzh18p5ykte',
            ]);
    }

    private function fetch_post()
    {
        FacebookFetchPagePost::dispatch('173455875839968', '122172781742096830', '', '', '', '');
    }

    private function fetch_page_token($params)
    {
        $page_id = $params[0];
        FacebookFetchPageToken::dispatch($page_id);
    }

    private function pull_campaign($params)
    {
        FacebookFetchCampaignV2::dispatch('01jcnd9vay3rbxk0z0bkxb07qb', null, null, null, true,);
    }

    private function pull_adset($params)
    {
        FacebookFetchAdsetV2::dispatch('01jcnd9vay3rbxk0z0bkxb07qb');
    }

    private function pull_ad($params)
    {
        FacebookFetchAdV2::dispatch('01jcnd9vay3rbxk0z0bkxb07qb');
    }

    private function sync_ad_account($params)
    {
        FacebookSyncAdAccount::dispatch('01jcnd9vay3rbxk0z0bkxb07qb', '2024-10-06', '2024-10-08', null, true);
    }

    private function assign_rule_to_admin()
    {
        $rules = Rule::query()->get();
        $admin_user = User::query()->firstWhere('name', 'admin');
        foreach ($rules as $rule ) {
            $rule->user_id = $admin_user->id;
            $rule->save();
        }
    }

    private function gen_provider_spending($params)
    {
        $date_start = $params[0];
        $date_stop = $params[1];
        GenProviderSpend::dispatch($date_start, $date_stop)->onQueue('facebook');
    }

    private function genCwPartnerSpending($params)
    {
        // 如果没有提供参数，使用默认的最近7天
        if (empty($params)) {
            $currentDate = Carbon::now('UTC')->addHours(8)->toDateString();
            $sevenDaysAgo = Carbon::now('UTC')->addHours(8)->subDays(7)->toDateString();

            // 确保开始时间不早于2025-10-30
            $minStartDate = '2025-10-30';
            if ($sevenDaysAgo < $minStartDate) {
                $sevenDaysAgo = $minStartDate;
            }

            $date_start = $sevenDaysAgo;
            $date_stop = $currentDate;
        } else {
            // 使用提供的参数
            $date_start = $params[0] ?? '2025-10-30';
            $date_stop = $params[1] ?? Carbon::now('UTC')->addHours(8)->toDateString();
        }

        $this->info("开始生成CW合作伙伴消耗数据...");
        $this->info("时间范围: {$date_start} 到 {$date_stop}");

        // 显示投手信息
        $cwPartners = [
            'cw_rt' => ['tag_name' => 'CW-RT', 'username' => 'admin'],
            'cw_hq' => ['tag_name' => 'CW-HQ', 'username' => 'haoquan.wang'],
            'cw_wh' => ['tag_name' => 'CW-WH', 'username' => 'wuhan'],
            'cw_ht' => ['tag_name' => 'CW-HT', 'username' => 'hutao'],
        ];

        $this->info("CW合作伙伴投手:");
        foreach ($cwPartners as $key => $info) {
            $this->info("  - {$key}: {$info['tag_name']} (用户: {$info['username']})");
        }

        // 调度Job
        GenCwPartnerSpend::dispatch($date_start, $date_stop)->onQueue('facebook');

        $this->info("✅ CW合作伙伴消耗统计任务已提交到队列");
        $this->info("请使用以下命令查看队列状态:");
        $this->info("php artisan queue:work");
        $this->info("php artisan horizon:status");
    }

    private function updateMaterialTypes()
    {
        $this->info('开始更新素材类型...');

        // 获取所有 type 字段为 null 的 Material 记录
        $materials = Material::whereNull('type')->get();

        if ($materials->isEmpty()) {
            $this->info('没有需要更新的素材记录');
            return;
        }

        $updatedCount = 0;
        $imageCount = 0;
        $videoCount = 0;
        $unknownCount = 0;

        foreach ($materials as $material) {
            $type = null;

            // 从原始文件名或文件名中获取扩展名
            $filename = $material->original_filename ?: $material->filename;
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            // 根据扩展名设置 type
            if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                $type = 'image';
                $imageCount++;
            } elseif (in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', 'qt', 'ogg'])) {
                $type = 'video';
                $videoCount++;
            } else {
                $unknownCount++;
                $this->warn("未知文件类型: {$filename} (扩展名: {$extension})");
                continue;
            }

            // 更新 Material 记录
            $material->type = $type;
            $material->save();
            $updatedCount++;
        }

        $this->info("素材类型更新完成！");
        $this->info("总共处理: {$materials->count()} 个素材");
        $this->info("成功更新: {$updatedCount} 个");
        $this->info("图片类型: {$imageCount} 个");
        $this->info("视频类型: {$videoCount} 个");

        if ($unknownCount > 0) {
            $this->warn("未知类型: {$unknownCount} 个");
        }

        Log::info("Material types updated", [
            'total_processed' => $materials->count(),
            'updated' => $updatedCount,
            'images' => $imageCount,
            'videos' => $videoCount,
            'unknown' => $unknownCount
        ]);
    }

    private function syncCardsAll($params)
    {
        if (empty($params)) {
            $this->error('请提供CardProvider的ID或名称');
            $this->info('用法: php artisan dummy sync-cards-all <provider_id_or_name> [options]');
            $this->info('示例: php artisan dummy sync-cards-all ap');
            $this->info('      php artisan dummy sync-cards-all ap --sync-cvc');
            $this->info('      php artisan dummy sync-cards-all ap --no-sync-cvc');
            $this->info('      php artisan dummy sync-cards-all 01jx1234567890abcdef --sync-cvc');
            $this->info('选项:');
            $this->info('  --sync-cvc      强制启用CVC同步');
            $this->info('  --no-sync-cvc   强制禁用CVC同步');
            $this->info('  （不指定选项时，Adpos Provider 默认启用CVC同步）');
            return;
        }

        $providerIdOrName = $params[0];
        $options = $this->parseOptions($params);

        // 尝试通过ID或name查找CardProvider
        $cardProvider = CardProvider::where('id', $providerIdOrName)
            ->orWhere('name', $providerIdOrName)
            ->first();

        if (!$cardProvider) {
            $this->error("找不到CardProvider: {$providerIdOrName}");

            // 显示可用的Provider列表
            $this->info('可用的CardProvider列表:');
            $providers = CardProvider::select('id', 'name', 'nick_name', 'active')->get();

            foreach ($providers as $provider) {
                $status = $provider->active ? '✅' : '❌';
                $this->line("{$status} ID: {$provider->id} | Name: {$provider->name} | Nick: {$provider->nick_name}");
            }

            return;
        }

        if (!$cardProvider->active) {
            $this->error("CardProvider '{$cardProvider->nick_name}' 未激活");
            return;
        }

        $this->info("开始同步CardProvider '{$cardProvider->nick_name}' 的所有卡片...");

        // 显示同步选项
        $optionsInfo = [];
        if (isset($options['sync_cvc'])) {
            $optionsInfo[] = 'sync_cvc: ' . ($options['sync_cvc'] ? '启用' : '禁用');
        } else {
            $optionsInfo[] = 'sync_cvc: 自动 (Adpos Provider 默认启用)';
        }

        if (!empty($optionsInfo)) {
            $this->info("同步选项: " . implode(', ', $optionsInfo));
        }

        try {
            // 分发CardSyncAll Job with options
            CardSyncAll::dispatch($cardProvider->id, $options)->onQueue('default');

            $this->info("同步任务已提交到队列");
            $this->info("Provider: {$cardProvider->nick_name} (ID: {$cardProvider->id})");
            $this->info("请查看队列状态和日志了解同步进度");

        } catch (\Exception $e) {
            $this->error("提交同步任务失败: {$e->getMessage()}");
            Log::error("Failed to dispatch CardSyncAll job", [
                'provider_id' => $cardProvider->id,
                'options' => $options,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function syncCard($params)
    {
        if (empty($params)) {
            $this->error('请提供一个或多个card_id参数');
            $this->info('用法: php artisan dummy sync-card <card_id1> [card_id2] [card_id3] ... [options]');
            $this->info('示例: php artisan dummy sync-card card123');
            $this->info('      php artisan dummy sync-card card123 card456 card789');
            $this->info('      php artisan dummy sync-card card123 --sync-cvv');
            $this->info('      php artisan dummy sync-card card123 card456 --sync-cvv');
            $this->info('选项:');
            $this->info('  --sync-cvv      启用CVV同步');
            $this->info('  （不指定选项时，默认不同步CVV）');
            return;
        }

        // 解析参数，区分card_id和选项
        $cardIds = [];
        $syncCvv = false;

        foreach ($params as $param) {
            if ($param === '--sync-cvv') {
                $syncCvv = true;
            } else {
                $cardIds[] = $param;
            }
        }

        if (empty($cardIds)) {
            $this->error('没有提供有效的card_id');
            return;
        }

        $this->info("开始同步卡片...");
        $this->info("卡片数量: " . count($cardIds));
        $this->info("同步CVV: " . ($syncCvv ? '是' : '否'));

        $successCount = 0;
        $failedCount = 0;

        foreach ($cardIds as $cardId) {
            try {
                // 检查卡片是否存在
                $card = \App\Models\Card::where('source_id', $cardId)->first();
                if (!$card) {
                    $this->warn("卡片不存在: {$cardId}");
                    $failedCount++;
                    continue;
                }

                // 分发CardSync job
                CardSync::dispatch($cardId, $syncCvv)->onQueue('default');

                $this->info("已提交同步任务: {$cardId}");
                $successCount++;

            } catch (\Exception $e) {
                $this->error("提交同步任务失败: {$cardId} - {$e->getMessage()}");
                $failedCount++;

                Log::error("Failed to dispatch CardSync job", [
                    'card_id' => $cardId,
                    'sync_cvv' => $syncCvv,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("同步任务提交完成!");
        $this->info("成功: {$successCount}, 失败: {$failedCount}");
        $this->info("请查看队列状态和日志了解同步进度");
    }

    /**
     * 解析命令行选项
     */
    private function parseOptions($params): array
    {
        $options = [];

        // 遍历参数寻找选项
        foreach ($params as $param) {
            if ($param === '--sync-cvc') {
                $options['sync_cvc'] = true;
            } elseif ($param === '--no-sync-cvc') {
                $options['sync_cvc'] = false;
            }
        }

        return $options;
    }


    private function syncTrans($params)
    {
        if (empty($params)) {
            $this->error('请提供一个或多个卡号参数');
            $this->info('用法: php artisan dummy sync-trans <card_number1> [card_number2] [card_number3] ...');
            $this->info('示例: php artisan dummy sync-trans 1234567890123456');
            $this->info('      php artisan dummy sync-trans 1234567890123456 9876543210987654');
            return;
        }

        $cardNumbers = $params;

        // 设置固定的时间范围：最近3天
        $startTime = strtotime('-3 days');
        $stopTime = strtotime('now');

        $this->info("开始同步交易记录...");
        $this->info("卡号数量: " . count($cardNumbers));
        $this->info("时间范围: " . date('Y-m-d H:i:s', $startTime) . " 至 " . date('Y-m-d H:i:s', $stopTime));

        $successCount = 0;
        $failedCount = 0;

        foreach ($cardNumbers as $index => $cardNumber) {
            try {
                // 通过卡号查找对应的卡片记录
                $card = \App\Models\Card::where('number', $cardNumber)->first();

                if (!$card) {
                    $this->warn("卡片不存在: {$cardNumber}");
                    $failedCount++;
                    continue;
                }

                if (!$card->source_id) {
                    $this->warn("卡片缺少source_id: {$cardNumber}");
                    $failedCount++;
                    continue;
                }

                $this->info("处理卡片: {$card->name} (卡号: {$cardNumber})");

                // 分发CardSyncTransactions job
                CardSyncTransactions::dispatch(
                    $startTime,           // start_time (最近3天)
                    $stopTime,            // stop_time (现在)
                    null,                 // after
                    null,                 // status
                    null,                 // provider
                    $card->source_id      // card_source_id
                )->onQueue('transactions')->delay(now()->addSeconds($index * 5));

                $successCount++;
                $this->info("✓ 任务已提交: {$card->name}");

            } catch (\Exception $e) {
                $this->error("处理卡片 {$cardNumber} 时出错: {$e->getMessage()}");
                $failedCount++;

                Log::error("Failed to sync transactions for card", [
                    'card_number' => $cardNumber,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("=== 同步任务提交完成 ===");
        $this->info("成功提交: {$successCount} 个任务");

        if ($failedCount > 0) {
            $this->warn("失败: {$failedCount} 个");
        }

        $this->info("请查看队列状态和日志了解同步进度");
        $this->info("队列: transactions");

        Log::info("Transaction sync command completed", [
            'card_numbers' => $cardNumbers,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'time_range' => [
                'start' => date('Y-m-d H:i:s', $startTime),
                'stop' => date('Y-m-d H:i:s', $stopTime)
            ]
        ]);
    }

    /**
     * 自动绑定卡片到广告账户
     * 根据 default_funding 的后4位匹配数据库中的卡片
     */
    private function autoBindCard()
    {
        $this->info("开始自动绑定卡片到广告账户...");

        // 只处理2025年1月1日之后创建的FbAdAccount
        $startDate = '2025-01-01 00:00:00';

        // 查询符合条件的广告账户：2025年后创建且没有绑定卡片
        $fbAdAccounts = FbAdAccount::where('created_at', '>', $startDate)
            ->whereDoesntHave('cards') // 没有绑定卡片的
            ->whereNotNull('default_funding') // default_funding不为空
            ->where('default_funding', '!=', '') // default_funding不为空字符串
            ->where('default_funding', '!=', 'monthly invoicing') // 排除月度账单
            ->get();

        $this->info("找到 {$fbAdAccounts->count()} 个符合条件的广告账户");

        $successCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($fbAdAccounts as $fbAdAccount) {
            try {
                // 提取default_funding的后4位
                $last4Digits = $this->extractLast4Digits($fbAdAccount->default_funding);

                if (!$last4Digits) {
                    Log::warning("无法提取后4位数字", [
                        'fb_ad_account_id' => $fbAdAccount->id,
                        'fb_ad_account_source_id' => $fbAdAccount->source_id,
                        'default_funding' => $fbAdAccount->default_funding
                    ]);
                    $this->warn("⚠ 跳过处理: {$fbAdAccount->name} (source_id: {$fbAdAccount->source_id}) - 无法提取后4位数字: {$fbAdAccount->default_funding}");
                    $skippedCount++;
                    continue;
                }

                // 查询数据库中number字段后4位匹配的卡片
                $matchingCards = Card::whereRaw('RIGHT(number, 4) = ?', [$last4Digits])->get();

                if ($matchingCards->count() === 0) {
                    Log::info("未找到匹配的卡片", [
                        'fb_ad_account_id' => $fbAdAccount->id,
                        'fb_ad_account_source_id' => $fbAdAccount->source_id,
                        'last_4_digits' => $last4Digits,
                        'default_funding' => $fbAdAccount->default_funding
                    ]);
                    $this->info("- 跳过处理: {$fbAdAccount->name} (source_id: {$fbAdAccount->source_id}) - 未找到后4位为 {$last4Digits} 的卡片");
                    $skippedCount++;
                    continue;
                }

                if ($matchingCards->count() > 1) {
                    Log::warning("找到多张匹配的卡片，跳过绑定", [
                        'fb_ad_account_id' => $fbAdAccount->id,
                        'fb_ad_account_source_id' => $fbAdAccount->source_id,
                        'last_4_digits' => $last4Digits,
                        'default_funding' => $fbAdAccount->default_funding,
                        'matching_cards_count' => $matchingCards->count(),
                        'card_ids' => $matchingCards->pluck('id')->toArray()
                    ]);
                    $this->warn("⚠ 跳过处理: {$fbAdAccount->name} (source_id: {$fbAdAccount->source_id}) - 找到 {$matchingCards->count()} 张后4位为 {$last4Digits} 的卡片");
                    $skippedCount++;
                    continue;
                }

                // 找到唯一匹配的卡片，执行绑定
                $card = $matchingCards->first();

                // 绑定卡片到广告账户，并设置为默认卡片
                $fbAdAccount->cards()->attach($card->id, ['is_default' => true]);

                Log::info("成功绑定卡片", [
                    'fb_ad_account_id' => $fbAdAccount->id,
                    'fb_ad_account_name' => $fbAdAccount->name,
                    'card_id' => $card->id,
                    'card_name' => $card->name,
                    'card_number_last_4' => $last4Digits,
                    'default_funding' => $fbAdAccount->default_funding
                ]);

                $this->info("✓ 绑定成功: {$fbAdAccount->name} -> {$card->name} (****{$last4Digits})");
                $successCount++;

            } catch (\Exception $e) {
                Log::error("绑定卡片时发生错误", [
                    'fb_ad_account_id' => $fbAdAccount->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->error("✗ 绑定失败: {$fbAdAccount->name} - {$e->getMessage()}");
                $errorCount++;
            }
        }

        $this->info("\n=== 自动绑定完成 ===");
        $this->info("成功绑定: {$successCount} 个");
        $this->info("跳过处理: {$skippedCount} 个");
        $this->info("处理失败: {$errorCount} 个");

        Log::info("Auto bind card completed", [
            'success_count' => $successCount,
            'skipped_count' => $skippedCount,
            'error_count' => $errorCount
        ]);
    }

    /**
     * 从default_funding字段提取后4位数字
     *
     * @param string $defaultFunding
     * @return string|null
     */
    private function extractLast4Digits($defaultFunding)
    {
        // 使用正则表达式提取 *后面的4位数字
        // 例如: "Mastercard *6253" -> "6253", "VISA *2829" -> "2829"
        if (preg_match('/\*(\d{4})/', $defaultFunding, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * 锁定未使用的卡片
     * 根据业务规则识别并freeze需要锁定的卡片
     */
        private function lockUnusedCard($params)
    {
        $dryRun = !empty($params) && in_array('dry-run', $params);

        $this->info("开始锁定未使用的卡片...");
        if ($dryRun) {
            $this->warn("⚠️  DRY-RUN 模式: 只输出信息，不执行真正的freeze操作");
        }

        // 加载白名单配置
        $whitelistEnabled = config('card_whitelist.whitelist_enabled', true);
        $whitelist = config('card_whitelist.freeze_whitelist', []);
        $matchMode = config('card_whitelist.match_mode', 'exact');

        if ($whitelistEnabled && !empty($whitelist)) {
            $this->info("🛡️  白名单已启用，排除 " . count($whitelist) . " 个卡号");
        }

        $cardProviderService = app(\App\Services\CardProviderService::class);

        $freezeCount = 0;
        $skipCount = 0;
        $errorCount = 0;
        $whitelistSkipCount = 0;

        // 场景1: 没有关联 FbAdAccount 的卡片，如果是 ACTIVE 状态，要 freeze
        $this->info("\n=== 场景1: 检查未关联广告账户的卡片 ===");
        $unlinkedCards = Card::with('cardProvider')
            ->whereDoesntHave('fbAdAccounts')
            ->where('status', 'ACTIVE')
            ->get();

        $this->info("找到 {$unlinkedCards->count()} 个未关联且为ACTIVE状态的卡片");

                        foreach ($unlinkedCards as $card) {
            $cardName = $card->name ?: '(无名称)';
            $this->info("卡片: {$cardName} | 卡号: {$card->number} | 状态: {$card->status} | 关联广告账户: 0个");

            // 检查白名单
            if ($whitelistEnabled && $this->isCardInWhitelist($card->number, $whitelist, $matchMode)) {
                $this->warn("🛡️  [白名单] 跳过冻结: {$cardName} - 卡号在白名单中");
                $whitelistSkipCount++;
                continue;
            }

            if (!$dryRun) {
                try {
                    $provider = $cardProviderService->getProviderByCard($card);
                    $success = $provider->freezeCard($card->source_id);

                    if ($success) {
                        $this->info("✓ 冻结成功: {$cardName}");
                        $freezeCount++;
                                    } else {
                    $this->error("✗ 冻结失败: {$cardName}");
                    $errorCount++;
                }
            } catch (\Exception $e) {
                $this->error("✗ 冻结异常: {$cardName} - {$e->getMessage()}");
                $errorCount++;
            }
        } else {
            $this->info("➤ [DRY-RUN] 将会冻结: {$cardName}");
        }
        }

                // 场景2: 检查关联了多个广告账户的卡片
        $this->info("\n=== 场景2: 检查关联多个广告账户的卡片 ===");
        $allCards = Card::with(['fbAdAccounts', 'cardProvider'])->get();
        $multiLinkedCards = $allCards->filter(function($card) {
            return $card->fbAdAccounts->count() > 1;
        });

        $this->info("找到 {$multiLinkedCards->count()} 个关联了多个广告账户的卡片");

                foreach ($multiLinkedCards as $card) {
            $activeAdAccounts = $card->fbAdAccounts->where('account_status', 'ACTIVE');
            $cardName = $card->name ?: '(无名称)';

            $this->info("卡片: {$cardName} | 卡号: {$card->number} | 状态: {$card->status}");
            $this->info("  关联广告账户数: {$card->fbAdAccounts->count()} | ACTIVE账户数: {$activeAdAccounts->count()}");

            foreach ($card->fbAdAccounts as $adAccount) {
                $adAccountName = $adAccount->name ?: '(无名称)';
                $this->info("  - 广告账户: {$adAccountName} | source_id: {$adAccount->source_id} | 状态: {$adAccount->account_status}");
            }

            if ($activeAdAccounts->count() > 0) {
                $this->warn("  ⚠️  有ACTIVE广告账户，不冻结此卡片");
                $skipCount++;
            }
        }

        // 场景3: FbAdAccount 状态不是 ACTIVE/UNSETTLED，但关联了 ACTIVE 卡片
        $this->info("\n=== 场景3: 检查非活跃广告账户关联的ACTIVE卡片 ===");
        $inactiveAdAccounts = FbAdAccount::with(['cards.cardProvider'])
            ->whereNotIn('account_status', ['ACTIVE', 'UNSETTLED'])
            ->whereHas('cards', function($query) {
                $query->where('status', 'ACTIVE');
            })
            ->get();

        $this->info("找到 {$inactiveAdAccounts->count()} 个非活跃广告账户关联了ACTIVE卡片");

                foreach ($inactiveAdAccounts as $adAccount) {
            $adAccountName = $adAccount->name ?: '(无名称)';
            $this->info("广告账户: {$adAccountName} | source_id: {$adAccount->source_id} | 状态: {$adAccount->account_status}");

            $activeCards = $adAccount->cards->where('status', 'ACTIVE');

            foreach ($activeCards as $card) {
                $cardName = $card->name ?: '(无名称)';
                $this->info("  卡片: {$cardName} | 卡号: {$card->number} | 状态: {$card->status}");

                // 检查这张卡片是否关联了其他ACTIVE的广告账户
                $otherActiveAdAccounts = $card->fbAdAccounts->where('account_status', 'ACTIVE')->where('id', '!=', $adAccount->id);

                                if ($otherActiveAdAccounts->count() > 0) {
                    $this->warn("    ⚠️  此卡片关联了其他ACTIVE广告账户，不冻结");
                    foreach ($otherActiveAdAccounts as $otherAdAccount) {
                        $otherAdAccountName = $otherAdAccount->name ?: '(无名称)';
                        $this->info("      - {$otherAdAccountName} (ACTIVE)");
                    }
                    $skipCount++;
                } else {
                    // 检查白名单
                    if ($whitelistEnabled && $this->isCardInWhitelist($card->number, $whitelist, $matchMode)) {
                        $this->warn("    🛡️  [白名单] 跳过冻结: {$cardName} - 卡号在白名单中");
                        $whitelistSkipCount++;
                        continue;
                    }
                    if (!$dryRun) {
                        try {
                            $provider = $cardProviderService->getProviderByCard($card);
                            $success = $provider->freezeCard($card->source_id);

                            if ($success) {
                                $this->info("    ✓ 冻结成功: {$cardName}");
                                $freezeCount++;
                            } else {
                                $this->error("    ✗ 冻结失败: {$cardName}");
                                $errorCount++;
                            }
                        } catch (\Exception $e) {
                            $this->error("    ✗ 冻结异常: {$cardName} - {$e->getMessage()}");
                            $errorCount++;
                        }
                    } else {
                        $this->info("    ➤ [DRY-RUN] 将会冻结: {$cardName}");
                    }
                }
            }
        }

        $this->info("\n=== 处理结果统计 ===");
        if ($dryRun) {
            $this->info("DRY-RUN模式 - 未执行实际操作");
        } else {
            $this->info("成功冻结: {$freezeCount} 个卡片");
        }
        $this->info("跳过处理: {$skipCount} 个卡片");
        if ($whitelistSkipCount > 0) {
            $this->info("🛡️  白名单跳过: {$whitelistSkipCount} 个卡片");
        }
        if ($errorCount > 0) {
            $this->error("处理失败: {$errorCount} 个卡片");
        }

        Log::info("Lock unused card command completed", [
            'dry_run' => $dryRun,
            'freeze_count' => $freezeCount,
            'skip_count' => $skipCount,
            'whitelist_skip_count' => $whitelistSkipCount,
            'error_count' => $errorCount,
            'whitelist_enabled' => $whitelistEnabled,
            'whitelist_cards_count' => count($whitelist)
        ]);
    }

    /**
     * 检查卡片是否在白名单中
     */
    private function isCardInWhitelist($cardNumber, $whitelist, $matchMode = 'exact')
    {
        if (empty($whitelist)) {
            return false;
        }

        foreach ($whitelist as $whitelistNumber) {
            if ($matchMode === 'exact') {
                if ($cardNumber === $whitelistNumber) {
                    return true;
                }
            } elseif ($matchMode === 'partial') {
                // 支持后几位匹配，例如白名单中的 "*1234" 匹配以1234结尾的卡号
                if (strpos($whitelistNumber, '*') === 0) {
                    $suffix = substr($whitelistNumber, 1);
                    if (substr($cardNumber, -strlen($suffix)) === $suffix) {
                        return true;
                    }
                } elseif ($cardNumber === $whitelistNumber) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 检查卡片消费与Facebook广告账户消费的差异
     * 输出格式: 卡片名称 | 卡号 | 卡片总交易 | FB广告账户总消费 | 差值 | FB账户列表
     */
    private function spendDiff()
    {
        $this->info("正在检查卡片与FB广告账户消费差异...\n");

        // 获取所有关联了FbAdAccount的卡片，预加载相关数据
        $cardsWithAdAccounts = Card::with([
            'transactions',
            'fbAdAccounts',
            'fbAdAccounts.insights'
        ])
        ->whereHas('fbAdAccounts')
        ->get();

        if ($cardsWithAdAccounts->isEmpty()) {
            $this->info("没有找到关联了FB广告账户的卡片");
            return;
        }

        $this->info("找到 {$cardsWithAdAccounts->count()} 张关联了FB广告账户的卡片\n");
        $this->info("格式: 卡片名称 | 卡号 | 卡片总交易 | FB广告账户总消费 | 差值 | FB账户source_id列表");
        $this->info(str_repeat("-", 120));

        foreach ($cardsWithAdAccounts as $card) {
            // 卡片名称处理
            $cardName = $card->name ?: '无名称';

            // 计算卡片总交易金额（计算除declined状态外的所有交易）
            $cardTotalTransactions = $card->transactions
                ->where('status', '!=', 'declined')
                ->sum('transaction_amount');

            // 获取所有关联的FB广告账户
            $fbAdAccounts = $card->fbAdAccounts;

            // 计算所有关联FB广告账户的总消费
            $totalFbSpend = 0;
            $fbAccountSourceIds = [];

            foreach ($fbAdAccounts as $fbAdAccount) {
                // 收集source_id
                $fbAccountSourceIds[] = $fbAdAccount->source_id;

                // 累加该广告账户的总消费（insights中的spend字段已经是美金）
                $adAccountSpend = $fbAdAccount->insights->sum('spend');
                $totalFbSpend += $adAccountSpend;
            }

            // 计算差值：卡片总消费 - FB广告账户总消费
            $difference = $cardTotalTransactions - $totalFbSpend;

            // 格式化输出
            $this->info(sprintf(
                "%s | %s | %.2f | %.2f | %.2f | %s",
                $cardName,
                $card->number,
                $cardTotalTransactions,
                $totalFbSpend,
                $difference,
                implode(' | ', $fbAccountSourceIds)
            ));
        }

        $this->info(str_repeat("-", 120));
        $this->info("检查完成！");

        // 记录日志
        Log::info("Spend difference check completed", [
            'cards_processed' => $cardsWithAdAccounts->count(),
            'timestamp' => now()
        ]);
    }

        /**
     * 检查卡片消费与Facebook广告账户消费的差异（只处理最近交易的transaction_date大于7天前的卡片）
     * 输出格式: 卡片名称 | 卡号 | 卡片总交易 | FB广告账户总消费 | 差值 | FB账户列表
     */
    private function spendDiffProcessed()
    {
        $this->info("正在检查卡片与FB广告账户消费差异（最近交易的transaction_date大于7天前）...\n");

        $sevenDaysAgo = now()->subDays(7);
        $this->info("过滤条件：最近交易的transaction_date早于 {$sevenDaysAgo->toDateString()}\n");

                        // 获取所有关联了FbAdAccount的卡片，并且最近交易的transaction_date大于7天前
        $cardsWithAdAccounts = Card::with([
            'transactions',
            'fbAdAccounts',
            'fbAdAccounts.insights'
        ])
        ->whereHas('fbAdAccounts')
        ->whereHas('transactions')  // 确保卡片有交易记录
        ->whereDoesntHave('transactions', function($query) use ($sevenDaysAgo) {
            // 过滤掉最近7天内有交易的卡片，只保留最近交易的transaction_date大于7天前的卡片
            $query->where('transaction_date', '>=', $sevenDaysAgo);
        })
        ->get();

        if ($cardsWithAdAccounts->isEmpty()) {
            $this->info("没有找到符合条件的卡片（关联了FB广告账户且最近交易的transaction_date大于7天前）");
            return;
        }

        $this->info("找到 {$cardsWithAdAccounts->count()} 张符合条件的卡片\n");
        $this->info("格式: 卡片名称 | 卡号 | 最近交易时间 | 卡片总交易 | FB广告账户总消费 | 差值 | FB账户source_id列表");
        $this->info(str_repeat("-", 140));

        foreach ($cardsWithAdAccounts as $card) {
            // 卡片名称处理
            $cardName = $card->name ?: '无名称';

            // 获取最近的交易时间
            $lastTransactionDate = $card->transactions->max('transaction_date');
            $lastTransactionDateFormatted = $lastTransactionDate ?
                \Carbon\Carbon::parse($lastTransactionDate)->toDateString() : '无交易';

            // 计算卡片总交易金额（计算除declined状态外的所有交易）
            $cardTotalTransactions = $card->transactions
                ->where('status', '!=', 'declined')
                ->sum('transaction_amount');

            // 获取所有关联的FB广告账户
            $fbAdAccounts = $card->fbAdAccounts;

            // 计算所有关联FB广告账户的总消费
            $totalFbSpend = 0;
            $fbAccountSourceIds = [];

            foreach ($fbAdAccounts as $fbAdAccount) {
                // 收集source_id
                $fbAccountSourceIds[] = $fbAdAccount->source_id;

                // 累加该广告账户的总消费（insights中的spend字段已经是美金）
                $adAccountSpend = $fbAdAccount->insights->sum('spend');
                $totalFbSpend += $adAccountSpend;
            }

            // 计算差值：卡片总消费 - FB广告账户总消费
            $difference = $cardTotalTransactions - $totalFbSpend;

            // 格式化输出（增加最近交易时间列）
            $this->info(sprintf(
                "%s | %s | %s | %.2f | %.2f | %.2f | %s",
                $cardName,
                $card->number,
                $lastTransactionDateFormatted,
                $cardTotalTransactions,
                $totalFbSpend,
                $difference,
                implode(' | ', $fbAccountSourceIds)
            ));
        }

        $this->info(str_repeat("-", 140));
        $this->info("检查完成！");

        // 记录日志
        Log::info("Processed spend difference check completed", [
            'cards_processed' => $cardsWithAdAccounts->count(),
            'filter_date' => $sevenDaysAgo->toDateString(),
            'timestamp' => now()
        ]);
    }

            /**
     * 修复 Mint-Rancher 网络的 sub_1 和 sub_2 数据交换
     */
    private function fixMintData()
    {
        $this->info("开始修复 Mint-Rancher 网络数据...");
        $this->info(str_repeat("=", 80));

        try {
            // 查找 Mint-Rancher 网络
            $network = Network::where('name', 'Mint-Rancher')->first();

            if (!$network) {
                $this->error("未找到名为 'Mint-Rancher' 的网络");
                return;
            }

            $this->info("找到网络: {$network->name} (ID: {$network->id})");
            $this->info("");

                                    // 修复 Conversion 表数据
            $this->info("处理 Conversion 表数据...");
            $conversionCutoffDate = '2025-06-10 00:00:00';

            $conversionsToFix = Conversion::where('network_id', $network->id)
                ->where('conversion_datetime', '>=', $conversionCutoffDate)
                ->get();

            $this->info("找到 {$conversionsToFix->count()} 条 Conversion 记录需要修复");

            $conversionFixed = 0;

            foreach ($conversionsToFix as $conversion) {
                // 交换 sub_1 和 sub_2
                $oldSub1 = $conversion->sub_1;
                $oldSub2 = $conversion->sub_2;

                $conversion->sub_1 = $oldSub2;
                $conversion->sub_2 = $oldSub1;
                $conversion->save();

                $conversionFixed++;

                $this->info("✓ Conversion ID: {$conversion->id} - 交换完成 (sub_1: '{$oldSub1}' → '{$oldSub2}', sub_2: '{$oldSub2}' → '{$oldSub1}')");
            }

            $this->info("");
            $this->info("Conversion 表修复完成：{$conversionFixed} 条记录");
            $this->info("");

            // 修复 Click 表数据
            $this->info("处理 Click 表数据...");
            $clickCutoffDate = '2025-06-14 08:38:37';

            $clicksToFix = Click::where('network_id', $network->id)
                ->where('click_datetime', '>', $clickCutoffDate)
                ->get();

            $this->info("找到 {$clicksToFix->count()} 条 Click 记录需要修复");

            $clickFixed = 0;

            foreach ($clicksToFix as $click) {
                // 交换 sub_1 和 sub_2
                $oldSub1 = $click->sub_1;
                $oldSub2 = $click->sub_2;

                $click->sub_1 = $oldSub2;
                $click->sub_2 = $oldSub1;
                $click->save();

                $clickFixed++;

                $this->info("✓ Click ID: {$click->id} - 交换完成 (sub_1: '{$oldSub1}' → '{$oldSub2}', sub_2: '{$oldSub2}' → '{$oldSub1}')");
            }

            $this->info("");
            $this->info("Click 表修复完成：{$clickFixed} 条记录");
            $this->info("");

            // 总结
            $this->info(str_repeat("=", 80));
            $this->info("修复总结:");
            $this->info("网络名称: Mint-Rancher");
            $this->info("Conversion 表修复: {$conversionFixed} 条记录 (时间范围: >= {$conversionCutoffDate})");
            $this->info("Click 表修复: {$clickFixed} 条记录 (时间范围: > {$clickCutoffDate})");
            $this->info("总计修复: " . ($conversionFixed + $clickFixed) . " 条记录");
            $this->info(str_repeat("=", 80));

            // 记录日志
            Log::info("Mint-Rancher data fix completed", [
                'network_id' => $network->id,
                'network_name' => $network->name,
                'conversions_fixed' => $conversionFixed,
                'clicks_fixed' => $clickFixed,
                'total_fixed' => $conversionFixed + $clickFixed,
                'conversion_cutoff_date' => $conversionCutoffDate,
                'click_cutoff_date' => $clickCutoffDate,
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            $this->error("修复过程中发生错误: " . $e->getMessage());
            Log::error("Mint-Rancher data fix error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()
            ]);
        }
    }

    /**
     * 修复 Clickstack 网络的 sub_1 和 sub_2 数据交换
     */
    private function fixClickstackData()
    {
        $this->info("开始修复 Clickstack 网络数据...");
        $this->info(str_repeat("=", 80));

        try {
            // 查找 Clickstack 网络
            $network = Network::where('name', 'Clickstack')->first();

            if (!$network) {
                $this->error("未找到名为 'Clickstack' 的网络");
                return;
            }

            $this->info("找到网络: {$network->name} (ID: {$network->id})");
            $this->info("");

            // 修复 Conversion 表数据
            $this->info("处理 Conversion 表数据...");
            $conversionCutoffDate = '2025-06-10 00:00:00';

            $conversionsToFix = Conversion::where('network_id', $network->id)
                ->where('conversion_datetime', '>=', $conversionCutoffDate)
                ->get();

            $this->info("找到 {$conversionsToFix->count()} 条 Conversion 记录需要修复");

            $conversionFixed = 0;

            foreach ($conversionsToFix as $conversion) {
                // 交换 sub_1 和 sub_2
                $oldSub1 = $conversion->sub_1;
                $oldSub2 = $conversion->sub_2;

                $conversion->sub_1 = $oldSub2;
                $conversion->sub_2 = $oldSub1;
                $conversion->save();

                $conversionFixed++;

                $this->info("✓ Conversion ID: {$conversion->id} - 交换完成 (sub_1: '{$oldSub1}' → '{$oldSub2}', sub_2: '{$oldSub2}' → '{$oldSub1}')");
            }

            $this->info("");
            $this->info("Conversion 表修复完成：{$conversionFixed} 条记录");
            $this->info("");

            // 修复 Click 表数据
            $this->info("处理 Click 表数据...");
            $clickCutoffDate = '2025-06-14 08:38:37';

            $clicksToFix = Click::where('network_id', $network->id)
                ->where('click_datetime', '>', $clickCutoffDate)
                ->get();

            $this->info("找到 {$clicksToFix->count()} 条 Click 记录需要修复");

            $clickFixed = 0;

            foreach ($clicksToFix as $click) {
                // 交换 sub_1 和 sub_2
                $oldSub1 = $click->sub_1;
                $oldSub2 = $click->sub_2;

                $click->sub_1 = $oldSub2;
                $click->sub_2 = $oldSub1;
                $click->save();

                $clickFixed++;

                $this->info("✓ Click ID: {$click->id} - 交换完成 (sub_1: '{$oldSub1}' → '{$oldSub2}', sub_2: '{$oldSub2}' → '{$oldSub1}')");
            }

            $this->info("");
            $this->info("Click 表修复完成：{$clickFixed} 条记录");
            $this->info("");

            // 总结
            $this->info(str_repeat("=", 80));
            $this->info("修复总结:");
            $this->info("网络名称: Clickstack");
            $this->info("Conversion 表修复: {$conversionFixed} 条记录 (时间范围: >= {$conversionCutoffDate})");
            $this->info("Click 表修复: {$clickFixed} 条记录 (时间范围: > {$clickCutoffDate})");
            $this->info("总计修复: " . ($conversionFixed + $clickFixed) . " 条记录");
            $this->info(str_repeat("=", 80));

            // 记录日志
            Log::info("Clickstack data fix completed", [
                'network_id' => $network->id,
                'network_name' => $network->name,
                'conversions_fixed' => $conversionFixed,
                'clicks_fixed' => $clickFixed,
                'total_fixed' => $conversionFixed + $clickFixed,
                'conversion_cutoff_date' => $conversionCutoffDate,
                'click_cutoff_date' => $clickCutoffDate,
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            $this->error("修复过程中发生错误: " . $e->getMessage());
            Log::error("Clickstack data fix error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()
            ]);
        }
    }

    /**
     * 修复 BW 网络的 sub_1 和 sub_2 数据交换
     */
    private function fixBwData()
    {
        $this->info("开始修复 BW 网络数据...");
        $this->info(str_repeat("=", 80));

        try {
            // 查找 BW 网络
            $network = Network::where('name', 'BW')->first();

            if (!$network) {
                $this->error("未找到名为 'BW' 的网络");
                return;
            }

            $this->info("找到网络: {$network->name} (ID: {$network->id})");
            $this->info("");

            // 修复 Conversion 表数据
            $this->info("处理 Conversion 表数据...");
            $conversionCutoffDate = '2025-06-10 00:00:00';

            $conversionsToFix = Conversion::where('network_id', $network->id)
                ->where('conversion_datetime', '>=', $conversionCutoffDate)
                ->get();

            $this->info("找到 {$conversionsToFix->count()} 条 Conversion 记录需要修复");

            $conversionFixed = 0;

            foreach ($conversionsToFix as $conversion) {
                // 交换 sub_1 和 sub_2
                $oldSub1 = $conversion->sub_1;
                $oldSub2 = $conversion->sub_2;

                $conversion->sub_1 = $oldSub2;
                $conversion->sub_2 = $oldSub1;
                $conversion->save();

                $conversionFixed++;

                $this->info("✓ Conversion ID: {$conversion->id} - 交换完成 (sub_1: '{$oldSub1}' → '{$oldSub2}', sub_2: '{$oldSub2}' → '{$oldSub1}')");
            }

            $this->info("");
            $this->info("Conversion 表修复完成：{$conversionFixed} 条记录");
            $this->info("");

            // 修复 Click 表数据
            $this->info("处理 Click 表数据...");
            $clickCutoffDate = '2025-06-14 08:38:37';

            $clicksToFix = Click::where('network_id', $network->id)
                ->where('click_datetime', '>', $clickCutoffDate)
                ->get();

            $this->info("找到 {$clicksToFix->count()} 条 Click 记录需要修复");

            $clickFixed = 0;

            foreach ($clicksToFix as $click) {
                // 交换 sub_1 和 sub_2
                $oldSub1 = $click->sub_1;
                $oldSub2 = $click->sub_2;

                $click->sub_1 = $oldSub2;
                $click->sub_2 = $oldSub1;
                $click->save();

                $clickFixed++;

                $this->info("✓ Click ID: {$click->id} - 交换完成 (sub_1: '{$oldSub1}' → '{$oldSub2}', sub_2: '{$oldSub2}' → '{$oldSub1}')");
            }

            $this->info("");
            $this->info("Click 表修复完成：{$clickFixed} 条记录");
            $this->info("");

            // 总结
            $this->info(str_repeat("=", 80));
            $this->info("修复总结:");
            $this->info("网络名称: BW");
            $this->info("Conversion 表修复: {$conversionFixed} 条记录 (时间范围: >= {$conversionCutoffDate})");
            $this->info("Click 表修复: {$clickFixed} 条记录 (时间范围: > {$clickCutoffDate})");
            $this->info("总计修复: " . ($conversionFixed + $clickFixed) . " 条记录");
            $this->info(str_repeat("=", 80));

            // 记录日志
            Log::info("BW data fix completed", [
                'network_id' => $network->id,
                'network_name' => $network->name,
                'conversions_fixed' => $conversionFixed,
                'clicks_fixed' => $clickFixed,
                'total_fixed' => $conversionFixed + $clickFixed,
                'conversion_cutoff_date' => $conversionCutoffDate,
                'click_cutoff_date' => $clickCutoffDate,
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            $this->error("修复过程中发生错误: " . $e->getMessage());
            Log::error("BW data fix error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()
            ]);
        }
    }

    /**
     * 修复 FbAdAccount 中状态为 DISABLED 的记录的 auto_sync 字段
     */
    private function fixFbAccAutoSync()
    {
        try {
            $this->info("开始修复FbAdAccount的auto_sync字段...");

            // 查找状态为 DISABLED 且 auto_sync 为 true 的广告账户
            $disabledAccounts = FbAdAccount::where('account_status', 'DISABLED')
                ->where('auto_sync', true)
                ->get();

            $totalCount = $disabledAccounts->count();
            $this->info("找到 {$totalCount} 个状态为 DISABLED 且 auto_sync 为 true 的广告账户");

            if ($totalCount === 0) {
                $this->info("没有需要修复的记录");
                return;
            }

            $updatedCount = 0;

            foreach ($disabledAccounts as $account) {
                $this->line("处理广告账户: {$account->name} (ID: {$account->source_id})");

                $account->auto_sync = false;
                $account->save();

                $updatedCount++;
                $this->info("✓ 已将广告账户 {$account->source_id} 的 auto_sync 设置为 false");
            }

            $this->info("=== 修复完成 ===");
            $this->info("总共处理了 {$updatedCount} 个广告账户");

            Log::info("FbAdAccount auto_sync fix completed", [
                'total_processed' => $updatedCount,
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            $this->error("修复过程中发生错误: " . $e->getMessage());
            Log::error("FbAdAccount auto_sync fix error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()
            ]);
        }
    }

    /**
     * 手动触发防盗刷扫描
     */
    private function fraudScan()
    {
        $this->info("开始手动防盗刷扫描...");

        try {
            // 分派防盗刷扫描Job
            \App\Jobs\FraudDetectionScanJob::dispatch()->onQueue('default');

            $this->info("✓ 防盗刷扫描任务已提交到队列");
            $this->info("请查看队列状态和日志了解扫描进度");
            $this->info("队列: default");

            Log::info("Manual fraud detection scan triggered", [
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            $this->error("提交防盗刷扫描任务失败: " . $e->getMessage());
            Log::error("Manual fraud detection scan failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()
            ]);
        }
    }

    /**
     * 手动检测指定广告账户的盗刷风险
     */
    private function fraudScanAdAccount($params)
    {
        if (empty($params)) {
            $this->error("请提供广告账户的source_id");
            $this->info("使用方法: php artisan dummy fraud-scan-ad-acc source_id1 source_id2 ...");
            return;
        }

        $this->info("开始检测指定广告账户的盗刷风险...");
        $this->info("广告账户数量: " . count($params));

        // 预检查：如果没有配置白名单，直接跳过
        if (!\App\Services\FraudDetectionService::shouldPerformScan()) {
            $this->warn("没有配置防盗刷白名单，跳过检测");
            return;
        }

        $fraudDetectionService = app(\App\Services\FraudDetectionService::class);
        $fraudActionsService = app(\App\Services\FraudActionsService::class);

        $processedCount = 0;
        $fraudCount = 0;
        $errorCount = 0;
        $notFoundCount = 0;

        foreach ($params as $adAccountSourceId) {
            try {
                $this->info("处理广告账户: {$adAccountSourceId}");

                // 查找广告账户下的所有活跃广告
                $ads = \App\Models\FbAd::whereHas('fbAdAccountV2', function ($query) use ($adAccountSourceId) {
                    $query->where('source_id', $adAccountSourceId);
                })
                ->whereNotIn('status', ['DELETED', 'ARCHIVED', 'PAUSED'])
                ->whereNotNull('creative')
                ->get();

                if ($ads->isEmpty()) {
                    $this->warn("  ⚠️  广告账户 {$adAccountSourceId} 没有找到活跃广告");
                    $notFoundCount++;
                    continue;
                }

                $this->info("  找到 {$ads->count()} 个活跃广告");

                $adAccountFraudCount = 0;
                foreach ($ads as $ad) {
                    try {
                        $detectionResult = $fraudDetectionService->checkAd($ad);

                        if ($detectionResult['is_fraud']) {
                            $adAccountFraudCount++;
                            $fraudCount++;
                            $this->warn("    🚨 异常广告: {$ad->source_id} - {$detectionResult['reason']}");

                            // 执行相应的行动
                            $fraudActionsService->executeActions($ad, $detectionResult);
                        } else {
                            $this->info("    ✓ 正常广告: {$ad->source_id}");
                        }

                        $processedCount++;

                    } catch (\Exception $e) {
                        $errorCount++;
                        $this->error("    ❌ 检测广告失败: {$ad->source_id} - {$e->getMessage()}");
                    }
                }

                if ($adAccountFraudCount > 0) {
                    $this->warn("  广告账户 {$adAccountSourceId} 发现 {$adAccountFraudCount} 个异常广告");
                } else {
                    $this->info("  ✓ 广告账户 {$adAccountSourceId} 所有广告都正常");
                }

            } catch (\Exception $e) {
                $errorCount++;
                $this->error("处理广告账户失败: {$adAccountSourceId} - {$e->getMessage()}");
            }
        }

        $this->info("=== 检测完成 ===");
        $this->info("处理的广告账户: " . count($params));
        $this->info("检测的广告数量: {$processedCount}");
        $this->info("发现异常广告: {$fraudCount}");
        if ($notFoundCount > 0) {
            $this->warn("未找到广告的账户: {$notFoundCount}");
        }
        if ($errorCount > 0) {
            $this->error("处理错误: {$errorCount}");
        }

        \Illuminate\Support\Facades\Log::info("Manual fraud scan for ad accounts completed", [
            'ad_accounts' => $params,
            'processed_ads' => $processedCount,
            'fraud_detected' => $fraudCount,
            'errors' => $errorCount,
            'timestamp' => now()
        ]);
    }

    /**
     * 手动检测指定广告的盗刷风险
     */
    private function fraudScanAd($params)
    {
        if (empty($params)) {
            $this->error("请提供广告的source_id");
            $this->info("使用方法: php artisan dummy fraud-scan-ad source_id1 source_id2 ...");
            return;
        }

        $this->info("开始检测指定广告的盗刷风险...");
        $this->info("广告数量: " . count($params));

        // 预检查：如果没有配置白名单，直接跳过
        if (!\App\Services\FraudDetectionService::shouldPerformScan()) {
            $this->warn("没有配置防盗刷白名单，跳过检测");
            return;
        }

        $fraudDetectionService = app(\App\Services\FraudDetectionService::class);
        $fraudActionsService = app(\App\Services\FraudActionsService::class);

        $processedCount = 0;
        $fraudCount = 0;
        $errorCount = 0;
        $notFoundCount = 0;

        foreach ($params as $adSourceId) {
            try {
                $this->info("处理广告: {$adSourceId}");

                // 查找广告
                $ad = \App\Models\FbAd::where('source_id', $adSourceId)->first();

                if (!$ad) {
                    $this->warn("  ⚠️  广告不存在: {$adSourceId}");
                    $notFoundCount++;
                    continue;
                }

                if (!$ad->creative) {
                    $this->warn("  ⚠️  广告没有creative数据: {$adSourceId}");
                    $notFoundCount++;
                    continue;
                }

                if (in_array($ad->status, ['DELETED', 'ARCHIVED'])) {
                    $this->warn("  ⚠️  广告已删除或归档: {$adSourceId}");
                    $notFoundCount++;
                    continue;
                }

                // 获取广告账户信息
                $adAccount = $ad->fbAdAccountV2;
                $adAccountInfo = $adAccount ? "({$adAccount->name} - {$adAccount->source_id})" : "(未知账户)";

                $detectionResult = $fraudDetectionService->checkAd($ad);

                if ($detectionResult['is_fraud']) {
                    $fraudCount++;
                    $this->warn("  🚨 异常广告: {$ad->source_id} {$adAccountInfo}");
                    $this->warn("      原因: {$detectionResult['reason']}");

                    // 执行相应的行动
                    $fraudActionsService->executeActions($ad, $detectionResult);
                    $this->info("      已执行防盗刷行动");
                } else {
                    $this->info("  ✓ 正常广告: {$ad->source_id} {$adAccountInfo}");
                    $this->info("      检查结果: {$detectionResult['reason']}");
                }

                $processedCount++;

            } catch (\Exception $e) {
                $errorCount++;
                $this->error("检测广告失败: {$adSourceId} - {$e->getMessage()}");
            }
        }

        $this->info("=== 检测完成 ===");
        $this->info("处理的广告: " . count($params));
        $this->info("成功检测: {$processedCount}");
        $this->info("发现异常: {$fraudCount}");
        if ($notFoundCount > 0) {
            $this->warn("未找到或无效: {$notFoundCount}");
        }
        if ($errorCount > 0) {
            $this->error("处理错误: {$errorCount}");
        }

        \Illuminate\Support\Facades\Log::info("Manual fraud scan for ads completed", [
            'ads' => $params,
            'processed' => $processedCount,
            'fraud_detected' => $fraudCount,
            'errors' => $errorCount,
            'timestamp' => now()
        ]);
    }

    private function genProviderSpend()
    {
        $currentDate = Carbon::now('UTC')->addHours(8)->toDateString();

//        $startOfMonth = Carbon::now('UTC')->addHours(8)->startOfMonth()->toDateString();
        $startOfMonth = '2025-06-15';

        GenProviderSpend::dispatch($startOfMonth, $currentDate);
    }

}
