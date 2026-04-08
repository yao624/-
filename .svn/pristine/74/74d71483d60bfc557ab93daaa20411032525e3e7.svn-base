<?php

namespace App\Services\CardProviders;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

/**
 * 具有扩展功能的Provider示例
 * 支持所有标准功能，还有特殊的扩展功能
 */
class ExtendedProvider extends BaseCardProvider
{
    private string $baseUrl;

    public function __construct(array $config = [])
    {
        $this->setConfig($config);
        $this->baseUrl = $config['base_url'] ?? 'https://api.extended.com';
    }

    /**
     * 定义支持的功能（支持所有标准功能）
     */
    protected function defineSupportedCapabilities(): array
    {
        return [
            self::CAPABILITY_CREATE_CARD,
            self::CAPABILITY_FREEZE_CARD,
            self::CAPABILITY_UNFREEZE_CARD,
            self::CAPABILITY_CANCEL_CARD,
            self::CAPABILITY_SYNC_CARD,
            self::CAPABILITY_SET_TOTAL_LIMIT,
            self::CAPABILITY_SET_PER_TRANSACTION_LIMIT,
            self::CAPABILITY_GET_CARD_DETAILS,
            self::CAPABILITY_SYNC_TRANSACTIONS,
            self::CAPABILITY_SYNC_ALL_TRANSACTIONS,
            self::CAPABILITY_GET_ALL_CARDS,
        ];
    }

    /**
     * 定义扩展功能（特殊功能）
     */
    protected function defineExtendedCapabilities(): array
    {
        return [
            'transferBalance' => '卡片间余额转移',
            'batchCreateCards' => '批量创建卡片',
            'reloadCard' => '卡片充值',
            'setCustomLimit' => '设置自定义限额',
            'blockMerchantCategory' => '封禁商户类别',
            'getSpendingAnalytics' => '获取消费分析',
            'schedulePayment' => '定时付款',
            'enableVirtualNumber' => '启用虚拟卡号',
        ];
    }

    /**
     * 认证获取Token
     */
    public function getToken(): string
    {
        $response = Http::post($this->baseUrl . '/oauth/token', [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'grant_type' => 'client_credentials'
        ]);

        if ($response->successful()) {
            return $response->json('access_token');
        }

        throw new Exception("Authentication failed");
    }

    /**
     * 创建卡片
     */
    public function createCard(string $cardName, float $balance, array $options = []): array
    {
        $response = Http::withToken($this->getToken())
            ->post($this->baseUrl . '/v2/cards', [
                'name' => $cardName,
                'balance' => $balance,
                'currency' => $options['currency'] ?? 'USD',
                'type' => $options['type'] ?? 'virtual'
            ]);

        if (!$response->successful()) {
            throw new Exception("Failed to create card");
        }

        $data = $response->json();

        return [
            'card_id' => $data['id'],
            'status' => $data['status'],
            'balance' => $data['balance'],
            'number' => $data['number'],
            'cvv' => $data['cvv'],
            'expiration' => $data['expiry']
        ];
    }

    // =========================
    // 标准功能实现
    // =========================

    protected function doFreezeCard(string $sourceId): bool
    {
        $response = Http::withToken($this->getToken())
            ->patch($this->baseUrl . "/v2/cards/{$sourceId}/freeze");

        return $response->successful();
    }

    protected function doUnfreezeCard(string $sourceId): bool
    {
        $response = Http::withToken($this->getToken())
            ->patch($this->baseUrl . "/v2/cards/{$sourceId}/unfreeze");

        return $response->successful();
    }

    protected function doCancelCard(string $sourceId): bool
    {
        $response = Http::withToken($this->getToken())
            ->delete($this->baseUrl . "/v2/cards/{$sourceId}");

        return $response->successful();
    }

    protected function doSyncCard(string $sourceId, bool $syncCvv = false): array
    {
        $endpoint = $this->baseUrl . "/v2/cards/{$sourceId}";
        if ($syncCvv) {
            $endpoint .= '?include_sensitive=true';
        }

        $response = Http::withToken($this->getToken())->get($endpoint);

        if (!$response->successful()) {
            throw new Exception("Failed to sync card");
        }

        $data = $response->json();

        return [
            'name' => $data['name'],
            'status' => $data['status'],
            'balance' => $data['balance'],
            'created_at' => Carbon::parse($data['created_at']),
            'currency' => $data['currency']
        ];
    }

    protected function doSetTotalLimit(string $sourceId, float $totalLimit): bool
    {
        $response = Http::withToken($this->getToken())
            ->patch($this->baseUrl . "/v2/cards/{$sourceId}/limits", [
                'total_limit' => $totalLimit
            ]);

        return $response->successful();
    }

    protected function doSetPerTransactionLimit(string $sourceId, float $perTransLimit): bool
    {
        $response = Http::withToken($this->getToken())
            ->patch($this->baseUrl . "/v2/cards/{$sourceId}/limits", [
                'per_transaction_limit' => $perTransLimit
            ]);

        return $response->successful();
    }

    protected function doGetCardDetails(string $sourceId): array
    {
        $response = Http::withToken($this->getToken())
            ->get($this->baseUrl . "/v2/cards/{$sourceId}/details");

        if (!$response->successful()) {
            throw new Exception("Failed to get card details");
        }

        return $response->json();
    }

    protected function doSyncTransactions(string $sourceId): array
    {
        $response = Http::withToken($this->getToken())
            ->get($this->baseUrl . "/v2/cards/{$sourceId}/transactions");

        if (!$response->successful()) {
            throw new Exception("Failed to sync transactions");
        }

        return $response->json('data', []);
    }

    protected function doSyncAllTransactions(): array
    {
        $response = Http::withToken($this->getToken())
            ->get($this->baseUrl . "/v2/transactions");

        if (!$response->successful()) {
            throw new Exception("Failed to sync all transactions");
        }

        return $response->json('data', []);
    }

    protected function doGetAllCards(array $options = []): array
    {
        $response = Http::withToken($this->getToken())
            ->get($this->baseUrl . "/v2/cards");

        if (!$response->successful()) {
            throw new Exception("Failed to get all cards");
        }

        return $response->json('data', []);
    }

    // =========================
    // 扩展功能实现
    // =========================

    /**
     * 扩展功能：卡片间余额转移
     */
    protected function doTransferBalance(string $fromCardId, string $toCardId, float $amount, string $reason = ''): array
    {
        $response = Http::withToken($this->getToken())
            ->post($this->baseUrl . "/v2/transfers", [
                'from_card_id' => $fromCardId,
                'to_card_id' => $toCardId,
                'amount' => $amount,
                'reason' => $reason
            ]);

        if (!$response->successful()) {
            throw new Exception("Failed to transfer balance");
        }

        return $response->json();
    }

    /**
     * 扩展功能：批量创建卡片
     */
    protected function doBatchCreateCards(array $cards): array
    {
        $response = Http::withToken($this->getToken())
            ->post($this->baseUrl . "/v2/cards/batch", [
                'cards' => $cards
            ]);

        if (!$response->successful()) {
            throw new Exception("Failed to batch create cards");
        }

        return $response->json('data', []);
    }

    /**
     * 扩展功能：卡片充值
     */
    protected function doReloadCard(string $sourceId, float $amount): bool
    {
        $response = Http::withToken($this->getToken())
            ->post($this->baseUrl . "/v2/cards/{$sourceId}/reload", [
                'amount' => $amount
            ]);

        return $response->successful();
    }

    /**
     * 扩展功能：设置自定义限额
     */
    protected function doSetCustomLimit(string $sourceId, string $limitType, float $amount, string $period = 'daily'): bool
    {
        $response = Http::withToken($this->getToken())
            ->post($this->baseUrl . "/v2/cards/{$sourceId}/custom-limits", [
                'limit_type' => $limitType,
                'amount' => $amount,
                'period' => $period
            ]);

        return $response->successful();
    }

    /**
     * 扩展功能：封禁商户类别
     */
    protected function doBlockMerchantCategory(string $sourceId, array $categories): bool
    {
        $response = Http::withToken($this->getToken())
            ->post($this->baseUrl . "/v2/cards/{$sourceId}/blocked-categories", [
                'categories' => $categories
            ]);

        return $response->successful();
    }

    /**
     * 扩展功能：获取消费分析
     */
    protected function doGetSpendingAnalytics(string $sourceId, string $period = 'month'): array
    {
        $response = Http::withToken($this->getToken())
            ->get($this->baseUrl . "/v2/cards/{$sourceId}/analytics", [
                'period' => $period
            ]);

        if (!$response->successful()) {
            throw new Exception("Failed to get spending analytics");
        }

        return $response->json();
    }

    /**
     * 扩展功能：定时付款
     */
    protected function doSchedulePayment(string $sourceId, array $paymentData): array
    {
        $response = Http::withToken($this->getToken())
            ->post($this->baseUrl . "/v2/cards/{$sourceId}/scheduled-payments", $paymentData);

        if (!$response->successful()) {
            throw new Exception("Failed to schedule payment");
        }

        return $response->json();
    }

    /**
     * 扩展功能：启用虚拟卡号
     */
    protected function doEnableVirtualNumber(string $sourceId, array $options = []): array
    {
        $response = Http::withToken($this->getToken())
            ->post($this->baseUrl . "/v2/cards/{$sourceId}/virtual-numbers", $options);

        if (!$response->successful()) {
            throw new Exception("Failed to enable virtual number");
        }

        return $response->json();
    }
}