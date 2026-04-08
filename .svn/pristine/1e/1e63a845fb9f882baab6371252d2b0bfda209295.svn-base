<?php

namespace App\Services\CardProviders;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

/**
 * 功能有限的Provider示例
 * 只支持基本的开卡和查询功能
 * 不支持冻结、取消等高级功能
 */
class BasicProvider extends BaseCardProvider
{
    private string $baseUrl;

    public function __construct(array $config = [])
    {
        $this->setConfig($config);
        $this->baseUrl = $config['base_url'] ?? 'https://api.basic.com';
    }

    /**
     * 定义支持的功能（只支持基本功能）
     */
    protected function defineSupportedCapabilities(): array
    {
        return [
            self::CAPABILITY_CREATE_CARD,        // 支持开卡
            self::CAPABILITY_SYNC_CARD,          // 支持同步卡片信息
            self::CAPABILITY_GET_CARD_DETAILS,   // 支持获取卡片详情
            self::CAPABILITY_SYNC_TRANSACTIONS,  // 支持同步交易
            // 不支持: freeze, unfreeze, cancel, limits, etc.
        ];
    }

    /**
     * 认证获取Token
     */
    public function getToken(): string
    {
        $response = Http::post($this->baseUrl . '/login', [
            'username' => $this->config['username'],
            'password' => $this->config['password']
        ]);

        if ($response->successful()) {
            return $response->json('token');
        }

        throw new Exception("Authentication failed");
    }

    /**
     * 创建卡片
     */
    public function createCard(string $cardName, float $balance, array $options = []): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '/card/create', [
            'card_name' => $cardName,
            'initial_balance' => $balance
        ]);

        if (!$response->successful()) {
            throw new Exception("Failed to create card: " . $response->body());
        }

        $data = $response->json();

        return [
            'card_id' => $data['card_id'],
            'status' => 'ACTIVE',
            'balance' => $data['balance'],
            'number' => $data['card_number'],
            'cvv' => $data['cvv'],
            'expiration' => $data['expiry']
        ];
    }

    /**
     * 同步卡片信息
     */
    protected function doSyncCard(string $sourceId, bool $syncCvv = false): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken()
        ])->get($this->baseUrl . "/card/{$sourceId}");

        if (!$response->successful()) {
            throw new Exception("Failed to sync card");
        }

        $data = $response->json();

        return [
            'name' => $data['name'],
            'status' => $data['status'],
            'balance' => $data['balance'],
            'created_at' => Carbon::parse($data['created_at']),
            'currency' => $data['currency'] ?? 'USD'
        ];
    }

    /**
     * 获取卡片详情
     */
    protected function doGetCardDetails(string $sourceId): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken()
        ])->get($this->baseUrl . "/card/{$sourceId}/details");

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
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken()
        ])->get($this->baseUrl . "/card/{$sourceId}/transactions");

        if (!$response->successful()) {
            throw new Exception("Failed to sync transactions");
        }

        $transactions = $response->json('transactions', []);

        return array_map(function($transaction) {
            return [
                'transaction_id' => $transaction['id'],
                'amount' => $transaction['amount'],
                'date' => Carbon::parse($transaction['date']),
                'type' => $transaction['type'],
                'status' => $transaction['status'],
                'merchant' => $transaction['merchant'] ?? 'Unknown'
            ];
        }, $transactions);
    }

    // 注意：这个Provider不实现以下方法，因为不支持这些功能：
    // - doFreezeCard
    // - doUnfreezeCard
    // - doCancelCard
    // - doSetTotalLimit
    // - doSetPerTransactionLimit
    // - doSyncAllTransactions
    // - doGetAllCards

    // 当调用这些方法时，BaseCardProvider会自动返回false或记录日志
}