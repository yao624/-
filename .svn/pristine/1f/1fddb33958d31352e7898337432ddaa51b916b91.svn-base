<?php

namespace App\Services\CardProviders;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

/**
 * 功能完整的Provider示例
 * 支持所有标准功能
 */
class FullFeatureProvider extends BaseCardProvider
{
    private string $baseUrl;

    public function __construct(array $config = [])
    {
        $this->setConfig($config);
        $this->baseUrl = $config['base_url'] ?? 'https://api.fullfeature.com';
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
     * 认证获取Token
     */
    public function getToken(): string
    {
        $response = Http::post($this->baseUrl . '/auth', [
            'api_key' => $this->config['api_key'],
            'api_secret' => $this->config['api_secret']
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
            ->post($this->baseUrl . '/cards', [
                'name' => $cardName,
                'balance' => $balance,
                'currency' => 'USD'
            ]);

        if (!$response->successful()) {
            throw new Exception("Failed to create card");
        }

        return $response->json();
    }

    /**
     * 冻结卡片
     */
    protected function doFreezeCard(string $sourceId): bool
    {
        $response = Http::withToken($this->getToken())
            ->post($this->baseUrl . "/cards/{$sourceId}/freeze");

        return $response->successful();
    }

    /**
     * 解冻卡片
     */
    protected function doUnfreezeCard(string $sourceId): bool
    {
        $response = Http::withToken($this->getToken())
            ->post($this->baseUrl . "/cards/{$sourceId}/unfreeze");

        return $response->successful();
    }

    /**
     * 取消卡片
     */
    protected function doCancelCard(string $sourceId): bool
    {
        $response = Http::withToken($this->getToken())
            ->post($this->baseUrl . "/cards/{$sourceId}/cancel");

        return $response->successful();
    }

    /**
     * 同步卡片信息
     */
    protected function doSyncCard(string $sourceId, bool $syncCvv = false): array
    {
        $response = Http::withToken($this->getToken())
            ->get($this->baseUrl . "/cards/{$sourceId}");

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

    /**
     * 设置总限额
     */
    protected function doSetTotalLimit(string $sourceId, float $totalLimit): bool
    {
        $response = Http::withToken($this->getToken())
            ->put($this->baseUrl . "/cards/{$sourceId}/limit", [
                'total_limit' => $totalLimit
            ]);

        return $response->successful();
    }

    /**
     * 设置单笔限额
     */
    protected function doSetPerTransactionLimit(string $sourceId, float $perTransLimit): bool
    {
        $response = Http::withToken($this->getToken())
            ->put($this->baseUrl . "/cards/{$sourceId}/transaction-limit", [
                'per_transaction_limit' => $perTransLimit
            ]);

        return $response->successful();
    }

    /**
     * 获取卡片详情
     */
    protected function doGetCardDetails(string $sourceId): array
    {
        $response = Http::withToken($this->getToken())
            ->get($this->baseUrl . "/cards/{$sourceId}/details");

        if (!$response->successful()) {
            throw new Exception("Failed to get card details");
        }

        return $response->json();
    }

    /**
     * 同步单张卡片交易
     */
    protected function doSyncTransactions(string $sourceId): array
    {
        $response = Http::withToken($this->getToken())
            ->get($this->baseUrl . "/cards/{$sourceId}/transactions");

        if (!$response->successful()) {
            throw new Exception("Failed to sync transactions");
        }

        return $response->json('data', []);
    }

    /**
     * 同步所有交易
     */
    protected function doSyncAllTransactions(): array
    {
        $response = Http::withToken($this->getToken())
            ->get($this->baseUrl . "/transactions");

        if (!$response->successful()) {
            throw new Exception("Failed to sync all transactions");
        }

        return $response->json('data', []);
    }

    /**
     * 获取所有卡片
     */
    protected function doGetAllCards(array $options = []): array
    {
        $response = Http::withToken($this->getToken())
            ->get($this->baseUrl . "/cards");

        if (!$response->successful()) {
            throw new Exception("Failed to get all cards");
        }

        return $response->json('data', []);
    }
}