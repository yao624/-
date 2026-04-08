<?php

namespace App\Contracts;

use App\Models\Card;

interface CardProviderInterface
{
    /**
     * 获取认证Token
     */
    public function getToken(): string;

    /**
     * 创建卡片
     */
    public function createCard(string $cardName, float $balance, array $options = []): array;

    /**
     * 冻结卡片
     */
    public function freezeCard(string $sourceId): bool;

    /**
     * 解冻卡片
     */
    public function unfreezeCard(string $sourceId): bool;

    /**
     * 取消/关闭卡片
     */
    public function cancelCard(string $sourceId): bool;

    /**
     * 同步卡片信息
     */
    public function syncCard(string $sourceId, bool $syncCvv = false): array;

    /**
     * 设置卡片总限额
     */
    public function setTotalLimit(string $sourceId, float $totalLimit): bool;

    /**
     * 设置单笔交易限额
     */
    public function setPerTransactionLimit(string $sourceId, float $perTransLimit): bool;

    /**
     * 获取卡片详情
     */
    public function getCardDetails(string $sourceId): array;

    /**
     * 同步卡片交易记录
     */
    public function syncTransactions(string $sourceId): array;

    /**
     * 同步所有卡片交易记录
     */
    public function syncAllTransactions(): array;

    /**
     * 获取所有卡片列表
     */
    public function getAllCards(array $options = []): array;

    /**
     * 生成请求ID
     */
    public function generateRequestId(string $function): string;

    /**
     * 设置provider配置
     */
    public function setConfig(array $config): void;

    /**
     * 获取provider配置
     */
    public function getConfig(): array;
}