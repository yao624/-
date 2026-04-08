<?php

namespace App\Services\CardProviders;

use App\Contracts\CardProviderInterface;
use App\Contracts\CardProviderCapabilitiesInterface;
use Exception;
use Illuminate\Support\Facades\Log;

abstract class BaseCardProvider implements CardProviderInterface, CardProviderCapabilitiesInterface
{
    protected array $config = [];

    // 定义标准功能常量
    const CAPABILITY_CREATE_CARD = 'create_card';
    const CAPABILITY_FREEZE_CARD = 'freeze_card';
    const CAPABILITY_UNFREEZE_CARD = 'unfreeze_card';
    const CAPABILITY_CANCEL_CARD = 'cancel_card';
    const CAPABILITY_SYNC_CARD = 'sync_card';
    const CAPABILITY_SET_TOTAL_LIMIT = 'set_total_limit';
    const CAPABILITY_SET_PER_TRANSACTION_LIMIT = 'set_per_transaction_limit';
    const CAPABILITY_SET_BALANCE = 'set_balance';
    const CAPABILITY_GET_CARD_DETAILS = 'get_card_details';
    const CAPABILITY_SYNC_TRANSACTIONS = 'sync_transactions';
    const CAPABILITY_SYNC_ALL_TRANSACTIONS = 'sync_all_transactions';
    const CAPABILITY_GET_ALL_CARDS = 'get_all_cards';

    /**
     * 定义此Provider支持的功能
     * 子类必须重写此方法
     */
    abstract protected function defineSupportedCapabilities(): array;

    /**
     * 定义此Provider的扩展功能
     * 子类可以重写此方法
     */
    protected function defineExtendedCapabilities(): array
    {
        return [];
    }

    /**
     * 检查Provider是否支持某个功能
     */
    public function supports(string $capability): bool
    {
        return in_array($capability, $this->getSupportedCapabilities());
    }

    /**
     * 获取Provider支持的所有功能列表
     */
    public function getSupportedCapabilities(): array
    {
        return $this->defineSupportedCapabilities();
    }

    /**
     * 获取Provider的扩展功能列表
     */
    public function getExtendedCapabilities(): array
    {
        return $this->defineExtendedCapabilities();
    }

    /**
     * 设置配置
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * 获取配置
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * 生成请求ID
     */
    public function generateRequestId(string $function): string
    {
        $timestamp = date('YmdHis');
        $randomStr = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
        return $function . '-' . $timestamp . '-' . $randomStr;
    }

    /**
     * 检查功能支持并执行或抛出异常
     */
    protected function requireCapability(string $capability, string $methodName): void
    {
        if (!$this->supports($capability)) {
            throw new Exception("Provider does not support {$capability} (method: {$methodName})");
        }
    }

    /**
     * 记录不支持的功能调用
     */
    protected function logUnsupportedMethod(string $methodName, array $params = []): void
    {
        Log::info("Unsupported method called on provider", [
            'provider' => static::class,
            'method' => $methodName,
            'params' => $params
        ]);
    }

    // =========================
    // 默认实现 - 可以被子类重写
    // =========================

    public function freezeCard(string $sourceId): bool
    {
        if (!$this->supports(self::CAPABILITY_FREEZE_CARD)) {
            $this->logUnsupportedMethod('freezeCard', ['sourceId' => $sourceId]);
            return false;
        }

        return $this->doFreezeCard($sourceId);
    }

    public function unfreezeCard(string $sourceId): bool
    {
        if (!$this->supports(self::CAPABILITY_UNFREEZE_CARD)) {
            $this->logUnsupportedMethod('unfreezeCard', ['sourceId' => $sourceId]);
            return false;
        }

        return $this->doUnfreezeCard($sourceId);
    }

    public function cancelCard(string $sourceId): bool
    {
        if (!$this->supports(self::CAPABILITY_CANCEL_CARD)) {
            $this->logUnsupportedMethod('cancelCard', ['sourceId' => $sourceId]);
            return false;
        }

        return $this->doCancelCard($sourceId);
    }

    public function syncCard(string $sourceId, bool $syncCvv = false): array
    {
        if (!$this->supports(self::CAPABILITY_SYNC_CARD)) {
            $this->logUnsupportedMethod('syncCard', ['sourceId' => $sourceId, 'syncCvv' => $syncCvv]);
            return $this->getDefaultCardData();
        }

        return $this->doSyncCard($sourceId, $syncCvv);
    }

    public function setTotalLimit(string $sourceId, float $totalLimit): bool
    {
        if (!$this->supports(self::CAPABILITY_SET_TOTAL_LIMIT)) {
            $this->logUnsupportedMethod('setTotalLimit', ['sourceId' => $sourceId, 'totalLimit' => $totalLimit]);
            return false;
        }

        return $this->doSetTotalLimit($sourceId, $totalLimit);
    }

    public function setPerTransactionLimit(string $sourceId, float $perTransLimit): bool
    {
        if (!$this->supports(self::CAPABILITY_SET_PER_TRANSACTION_LIMIT)) {
            $this->logUnsupportedMethod('setPerTransactionLimit', ['sourceId' => $sourceId, 'perTransLimit' => $perTransLimit]);
            return false;
        }

        return $this->doSetPerTransactionLimit($sourceId, $perTransLimit);
    }

    public function setBalance(string $sourceId, float $balance): bool
    {
        if (!$this->supports(self::CAPABILITY_SET_BALANCE)) {
            $this->logUnsupportedMethod('setBalance', ['sourceId' => $sourceId, 'balance' => $balance]);
            return false;
        }

        return $this->doSetBalance($sourceId, $balance);
    }

    public function getCardDetails(string $sourceId): array
    {
        if (!$this->supports(self::CAPABILITY_GET_CARD_DETAILS)) {
            $this->logUnsupportedMethod('getCardDetails', ['sourceId' => $sourceId]);
            return [];
        }

        return $this->doGetCardDetails($sourceId);
    }

    public function syncTransactions(string $sourceId): array
    {
        if (!$this->supports(self::CAPABILITY_SYNC_TRANSACTIONS)) {
            $this->logUnsupportedMethod('syncTransactions', ['sourceId' => $sourceId]);
            return [];
        }

        return $this->doSyncTransactions($sourceId);
    }

    public function syncTransactionsWithParams(array $params = []): array
    {
        if (!$this->supports(self::CAPABILITY_SYNC_TRANSACTIONS)) {
            $this->logUnsupportedMethod('syncTransactionsWithParams', $params);
            return [
                'transactions' => [],
                'has_more' => false,
                'next_page' => null
            ];
        }

        return $this->doSyncTransactionsWithParams($params);
    }

    public function syncAllTransactions(): array
    {
        if (!$this->supports(self::CAPABILITY_SYNC_ALL_TRANSACTIONS)) {
            $this->logUnsupportedMethod('syncAllTransactions');
            return [];
        }

        return $this->doSyncAllTransactions();
    }

    public function getAllCards(array $options = []): array
    {
        if (!$this->supports(self::CAPABILITY_GET_ALL_CARDS)) {
            $this->logUnsupportedMethod('getAllCards');
            return [];
        }

        return $this->doGetAllCards($options);
    }

    // =========================
    // 扩展功能支持 - 魔术方法
    // =========================

    /**
     * 魔术方法：支持调用扩展功能
     */
    public function __call(string $method, array $arguments)
    {
        // 检查是否是扩展功能
        $extendedCapabilities = $this->getExtendedCapabilities();

        if (isset($extendedCapabilities[$method])) {
            return $this->callExtendedMethod($method, $arguments);
        }

        throw new Exception("Method {$method} not found in provider " . static::class);
    }

    /**
     * 调用扩展方法
     */
    protected function callExtendedMethod(string $method, array $arguments)
    {
        $methodName = 'do' . ucfirst($method);

        if (method_exists($this, $methodName)) {
            return $this->$methodName(...$arguments);
        }

        throw new Exception("Extended method {$method} is not implemented in provider " . static::class);
    }

    // =========================
    // 抽象方法 - 子类必须实现
    // =========================

    abstract public function getToken(): string;
    abstract public function createCard(string $cardName, float $balance, array $options = []): array;

    // =========================
    // 可选实现方法 - 子类可以重写
    // =========================

    protected function doFreezeCard(string $sourceId): bool
    {
        throw new Exception("freezeCard not implemented");
    }

    protected function doUnfreezeCard(string $sourceId): bool
    {
        throw new Exception("unfreezeCard not implemented");
    }

    protected function doCancelCard(string $sourceId): bool
    {
        throw new Exception("cancelCard not implemented");
    }

    protected function doSyncCard(string $sourceId, bool $syncCvv = false): array
    {
        throw new Exception("syncCard not implemented");
    }

    protected function doSetTotalLimit(string $sourceId, float $totalLimit): bool
    {
        throw new Exception("setTotalLimit not implemented");
    }

    protected function doSetPerTransactionLimit(string $sourceId, float $perTransLimit): bool
    {
        throw new Exception("setPerTransactionLimit not implemented");
    }

    protected function doSetBalance(string $sourceId, float $balance): bool
    {
        throw new Exception("setBalance not implemented");
    }

    protected function doGetCardDetails(string $sourceId): array
    {
        throw new Exception("getCardDetails not implemented");
    }

    protected function doSyncTransactions(string $sourceId): array
    {
        throw new Exception("syncTransactions not implemented");
    }

    protected function doSyncTransactionsWithParams(array $params = []): array
    {
        throw new Exception("syncTransactionsWithParams not implemented");
    }

    protected function doSyncAllTransactions(): array
    {
        throw new Exception("syncAllTransactions not implemented");
    }

    protected function doGetAllCards(array $options = []): array
    {
        throw new Exception("getAllCards not implemented");
    }

    /**
     * 获取默认的卡片数据
     */
    protected function getDefaultCardData(): array
    {
        return [
            'name' => 'Unknown',
            'status' => 'ACTIVE',
            'balance' => 0,
            'created_at' => now(),
            'currency' => 'USD'
        ];
    }
}