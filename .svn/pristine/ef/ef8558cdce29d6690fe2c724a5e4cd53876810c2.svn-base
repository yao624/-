<?php

namespace App\Services\CardProviders;

use App\Contracts\CardProviderInterface;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MinimalNewProviderProvider implements CardProviderInterface
{
    private array $config;
    private string $baseUrl;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->baseUrl = $config['base_url'] ?? 'https://api.newprovider.com';
    }

    // =========================
    // 🔴 核心必须方法 - 第一阶段
    // =========================

    public function setConfig(array $config): void
    {
        $this->config = $config;
        $this->baseUrl = $config['base_url'] ?? 'https://api.newprovider.com';
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getToken(): string
    {
        $cacheKey = 'NEW_PROVIDER_TOKEN';

        $token = Cache::remember($cacheKey, 1500, function () {
            return $this->authenticate();
        });

        if (!$token) {
            throw new Exception("Failed to authenticate with new provider");
        }

        return $token;
    }

    private function authenticate(): string
    {
        $apiKey = $this->config['api_key'] ?? '';
        $apiSecret = $this->config['api_secret'] ?? '';

        // 根据新Provider的认证方式实现
        $response = Http::withHeaders([
            'X-API-Key' => $apiKey,
            'X-API-Secret' => $apiSecret
        ])->post($this->baseUrl . '/auth');

        if ($response->successful()) {
            return $response->json('access_token');
        }

        Log::warning("Failed to authenticate with new provider");
        Log::warning($response->body());
        return '';
    }

    public function createCard(string $cardName, float $balance, array $options = []): array
    {
        $token = $this->getToken();
        $endpoint = $this->baseUrl . '/cards';

        // 根据新Provider的API格式调整
        $payload = [
            'name' => $cardName,
            'initial_balance' => $balance,
            'currency' => 'USD',
            'type' => 'virtual'
        ];

        $response = Http::withToken($token)
            ->timeout(60)
            ->post($endpoint, $payload);

        if (!$response->successful()) {
            Log::error("Failed to create card: {$response->body()}");
            throw new Exception("Failed to create card");
        }

        $data = $response->json();

        // 根据返回格式调整映射
        return [
            'card_id' => $data['id'],
            'status' => $this->mapStatus($data['status']),
            'balance' => $data['balance'],
            'number' => $data['card_number'],
            'cvv' => $data['cvv'],
            'expiration' => $data['expiry_date']
        ];
    }

    public function generateRequestId(string $function): string
    {
        $timestamp = date('YmdHis');
        $randomStr = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
        return $function . '-' . $timestamp . '-' . $randomStr;
    }

    // =========================
    // 🟡 可以暂时留空的方法 - 后续阶段
    // =========================

    public function freezeCard(string $sourceId): bool
    {
        // TODO: 暂时不实现，直接返回false
        Log::info("freezeCard not implemented yet for sourceId: {$sourceId}");
        return false;

        // 后续实现：
        // $token = $this->getToken();
        // $endpoint = $this->baseUrl . "/cards/{$sourceId}/freeze";
        // $response = Http::withToken($token)->post($endpoint);
        // return $response->successful();
    }

    public function unfreezeCard(string $sourceId): bool
    {
        // TODO: 暂时不实现，直接返回false
        Log::info("unfreezeCard not implemented yet for sourceId: {$sourceId}");
        return false;
    }

    public function cancelCard(string $sourceId): bool
    {
        // TODO: 暂时不实现，直接返回false
        Log::info("cancelCard not implemented yet for sourceId: {$sourceId}");
        return false;
    }

    public function syncCard(string $sourceId, bool $syncCvv = false): array
    {
        // TODO: 暂时返回空数组，避免系统报错
        Log::info("syncCard not implemented yet for sourceId: {$sourceId}");
        return [
            'name' => 'Unknown',
            'status' => 'ACTIVE',
            'balance' => 0,
            'created_at' => now(),
            'currency' => 'USD'
        ];
    }

    public function setTotalLimit(string $sourceId, float $totalLimit): bool
    {
        // TODO: 暂时不实现
        Log::info("setTotalLimit not implemented yet for sourceId: {$sourceId}, limit: {$totalLimit}");
        return false;
    }

    public function setPerTransactionLimit(string $sourceId, float $perTransLimit): bool
    {
        // TODO: 暂时不实现
        Log::info("setPerTransactionLimit not implemented yet for sourceId: {$sourceId}, limit: {$perTransLimit}");
        return false;
    }

    public function getCardDetails(string $sourceId): array
    {
        // TODO: 暂时返回空数组
        Log::info("getCardDetails not implemented yet for sourceId: {$sourceId}");
        return [];
    }

    public function syncTransactions(string $sourceId): array
    {
        // TODO: 暂时返回空数组
        Log::info("syncTransactions not implemented yet for sourceId: {$sourceId}");
        return [];
    }

    public function syncAllTransactions(): array
    {
        // TODO: 暂时返回空数组
        Log::info("syncAllTransactions not implemented yet");
        return [];
    }

    public function getAllCards(array $options = []): array
    {
        // TODO: 暂时返回空数组
        Log::info("getAllCards not implemented yet");
        return [];
    }

    // =========================
    // 🔧 辅助方法
    // =========================

    private function mapStatus(string $providerStatus): string
    {
        // 将新Provider的状态映射到系统标准状态
        $statusMap = [
            'active' => 'ACTIVE',
            'inactive' => 'INACTIVE',
            'frozen' => 'INACTIVE',
            'blocked' => 'INACTIVE',
            'cancelled' => 'CLOSED',
            'closed' => 'CLOSED'
        ];

        return $statusMap[strtolower($providerStatus)] ?? 'INACTIVE';
    }
}